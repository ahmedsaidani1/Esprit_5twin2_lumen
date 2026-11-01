<?php

namespace Tests\Feature;

use App\Models\Communaute;
use App\Models\Echange;
use App\Models\Installation;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PragmaRX\Google2FA\Google2FA;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Couvre la grille "Gestion user" : Fortify, reset, vérification d'e-mail, rôles Spatie, 2FA, profil. */
class GestionUtilisateurTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Authentification de base (Fortify) ----------

    public function test_auth_pages_are_served_by_fortify_with_our_views(): void
    {
        foreach (['/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertOk()->assertSee('SolarShare');
        }
        $this->get('/reset-password/token-quelconque?email=a@b.c')->assertOk()->assertSee('nouveau mot de passe');
    }

    public function test_error_messages_are_clear_and_in_french(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'mauvais'])
            ->assertSessionHasErrors(['email' => 'Ces identifiants ne correspondent à aucun compte.']);

        $this->post('/register', ['name' => '', 'email' => 'pas-un-email', 'password' => 'abc', 'password_confirmation' => 'x'])
            ->assertSessionHasErrors([
                'name' => 'Le champ nom est obligatoire.',
                'email' => 'Le champ e-mail doit être une adresse e-mail valide.',
            ]);
    }

    public function test_login_is_rate_limited(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 5) as $i) {
            $this->post('/login', ['email' => $user->email, 'password' => 'mauvais']);
        }
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertStatus(429);
        $this->assertGuest();
    }

    // ---------- Mot de passe oublié ----------

    public function test_password_reset_link_is_emailed_and_works(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
            $token = $n->token;
            return true;
        });

        $this->get("/reset-password/{$token}?email={$user->email}")->assertOk();

        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'nouveau123', 'password_confirmation' => 'nouveau123',
        ])->assertRedirect('/login')->assertSessionHas('status', __('passwords.reset'));

        $this->assertTrue(Hash::check('nouveau123', $user->fresh()->password));
    }

    public function test_reset_token_expires_after_60_minutes_and_is_single_use(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email]);

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;
            return true;
        });

        $this->travel(61)->minutes();
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'nouveau123', 'password_confirmation' => 'nouveau123',
        ])->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_unknown_email_gets_the_same_neutral_answer(): void
    {
        Notification::fake();
        $this->post('/forgot-password', ['email' => 'inconnu@example.com'])->assertSessionHasErrors('email');
        Notification::assertNothingSent();
    }

    // ---------- Vérification d'e-mail ----------

    public function test_registration_sends_a_verification_email_and_blocks_the_app_until_verified(): void
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'Nouveau', 'email' => 'nouveau@example.com',
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect('/');

        $user = User::where('email', 'nouveau@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/')->assertRedirect('/email/verify');
        $this->get('/email/verify')->assertOk()->assertSee($user->email);
        $this->get('/profil')->assertOk(); // le profil reste accessible pour corriger son e-mail

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect();

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get('/')->assertOk();
    }

    public function test_expired_or_forged_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        $expire = URL::temporarySignedRoute('verification.verify', now()->subMinute(),
            ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($expire)->assertForbidden();

        $mauvaisHash = URL::temporarySignedRoute('verification.verify', now()->addHour(),
            ['id' => $user->id, 'hash' => sha1('autre@example.com')]);
        $this->get($mauvaisHash)->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_changing_email_requires_a_new_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->put('/user/profile-information', ['name' => $user->name, 'email' => 'neuf@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertNull($user->fresh()->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // ---------- Rôles et permissions (Spatie) ----------

    public function test_roles_and_permissions_come_from_spatie(): void
    {
        $this->assertEqualsCanonicalizing(['admin', 'membre'], Role::pluck('name')->all());
        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('gerer-utilisateurs'));
        $this->assertFalse(Role::findByName('membre')->hasPermissionTo('gerer-utilisateurs'));
        $this->assertTrue(Role::findByName('membre')->hasPermissionTo('publier-offres'));

        $admin = User::factory()->admin()->create();
        $membre = User::factory()->create();
        $this->assertTrue($admin->isAdmin());
        $this->assertSame('membre', $membre->role);
    }

    public function test_permission_middleware_protects_user_management_on_the_server(): void
    {
        $membre = User::factory()->create();
        $this->actingAs($membre)->get('/users')->assertForbidden();
        $this->actingAs($membre)->get('/users/create')->assertForbidden();

        // retirer une permission au rôle suffit à couper l'accès, sans toucher au code
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/users')->assertOk();
        Role::findByName('admin')->revokePermissionTo('gerer-utilisateurs');
        $this->actingAs($admin->fresh())->get('/users')->assertForbidden();
    }

    public function test_admin_changes_roles_through_spatie(): void
    {
        $admin = User::factory()->admin()->create();
        $membre = User::factory()->create();

        $this->actingAs($admin)->put("/users/{$membre->id}", [
            'name' => $membre->name, 'email' => $membre->email, 'role' => 'admin',
        ])->assertRedirect('/users');

        $this->assertTrue($membre->fresh()->hasRole('admin'));
        $this->get('/users?role=admin')->assertOk()->assertSee($membre->email);
    }

    // ---------- Double authentification ----------

    /**
     * Code TOTP de la période suivante : Fortify refuse de réutiliser un code déjà consommé
     * (protection anti-rejeu), or l'activation vient d'utiliser celui de la période courante.
     */
    private function codeSuivant(string $secret): string
    {
        $g = app(Google2FA::class);
        return $g->oathTotp($secret, $g->getTimestamp() + 1);
    }

    private function activer2FA(User $user): string
    {
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);
        $this->post('/user/two-factor-authentication')->assertSessionHas('status', 'two-factor-authentication-enabled');

        $secret = decrypt($user->fresh()->two_factor_secret);
        $code = app(Google2FA::class)->getCurrentOtp($secret);
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $code])
            ->assertSessionHas('status', 'two-factor-authentication-confirmed');

        return $secret;
    }

    public function test_enabling_2fa_requires_a_recent_password_confirmation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/user/two-factor-authentication')->assertRedirect('/user/confirm-password');
        $this->assertNull($user->fresh()->two_factor_secret);

        $this->get('/profil')->assertSee('Confirmer mon mot de passe');
        $this->get('/profil/confirmer')->assertRedirect('/user/confirm-password');
        $this->post('/user/confirm-password', ['password' => 'password'])->assertRedirect('/profil#2fa');
    }

    public function test_user_enables_2fa_with_qr_code_and_recovery_codes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()]);

        $this->post('/user/two-factor-authentication');
        $this->get('/profil')->assertOk()->assertSee('<svg', false)->assertSee('Scannez ce QR code');

        // un mauvais code ne confirme pas
        $this->post('/user/confirmed-two-factor-authentication', ['code' => '000000'])
            ->assertSessionHasErrorsIn('confirmTwoFactorAuthentication', 'code');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $code = app(Google2FA::class)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));
        $this->post('/user/confirmed-two-factor-authentication', ['code' => $code]);
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $codes = $user->fresh()->recoveryCodes();
        $this->assertCount(8, $codes);
        $this->get('/profil')->assertSee($codes[0])->assertSee('La double authentification est activée');
    }

    public function test_login_with_2fa_asks_for_a_code_then_accepts_totp_or_a_recovery_code_once(): void
    {
        $user = User::factory()->create();
        $secret = $this->activer2FA($user);
        $recovery = $user->fresh()->recoveryCodes()[0];
        $this->post('/logout');

        // 1. mot de passe correct -> écran du code, pas encore connecté
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/two-factor-challenge');
        $this->assertGuest();
        $this->get('/two-factor-challenge')->assertOk()->assertSee('Double authentification');

        // 2. mauvais code refusé
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // 3. bon code TOTP
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['code' => $this->codeSuivant($secret)])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout');

        // 4. code de récupération : accepté une seule fois
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains($recovery, $user->fresh()->recoveryCodes());
    }

    public function test_user_can_disable_2fa(): void
    {
        $user = User::factory()->create();
        $this->activer2FA($user);

        $this->withSession(['auth.password_confirmed_at' => time()])->delete('/user/two-factor-authentication')
            ->assertSessionHas('status', 'two-factor-authentication-disabled');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_api_login_also_requires_the_second_factor(): void
    {
        $user = User::factory()->create();
        $secret = $this->activer2FA($user);
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password', 'code' => '000000'])
            ->assertStatus(422);
        $this->postJson('/api/login', [
            'email' => $user->email, 'password' => 'password', 'code' => $this->codeSuivant($secret),
        ])->assertOk()->assertJsonPath('user.two_factor', true);
    }

    // ---------- Profil : photo et suppression du compte ----------

    public function test_profile_photo_upload_is_validated_stored_and_served(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->put('/user/profile-information', [
            'name' => $user->name, 'email' => $user->email,
            'photo' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
        ])->assertSessionHasErrorsIn('updateProfileInformation', 'photo');

        $this->put('/user/profile-information', [
            'name' => $user->name, 'email' => $user->email,
            'photo' => UploadedFile::fake()->image('moi.jpg', 300, 300),
        ])->assertSessionHasNoErrors();

        $path = $user->fresh()->photo_path;
        $this->assertNotNull($path);
        Storage::disk('local')->assertExists($path);
        $this->get($user->fresh()->photoUrl())->assertOk();
        $this->get('/profil')->assertSee('Photo de profil');

        $this->put('/user/profile-information', ['name' => $user->name, 'email' => $user->email, 'supprimer_photo' => '1']);
        $this->assertNull($user->fresh()->photo_path);
        Storage::disk('local')->assertMissing($path);
    }

    public function test_user_deletes_their_account_with_password_confirmation(): void
    {
        $user = User::factory()->create();
        $toit = Installation::create(['user_id' => $user->id, 'nom' => 'Toit', 'type' => 'solaire', 'puissance_kw' => 3]);

        $this->actingAs($user)->delete('/profil', ['password' => 'mauvais'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertModelExists($user);

        $this->delete('/profil', ['password' => 'password'])->assertRedirect('/login');
        $this->assertGuest();
        $this->assertModelMissing($user);
        $this->assertModelMissing($toit);
    }

    public function test_account_with_exchanges_or_last_admin_cannot_be_deleted(): void
    {
        $vendeur = User::factory()->create();
        $acheteur = User::factory()->create();
        $communaute = Communaute::create(['nom' => 'C']);
        Echange::create([
            'vendeur_id' => $vendeur->id, 'acheteur_id' => $acheteur->id, 'communaute_id' => $communaute->id,
            'quantite_kwh' => 5, 'prix_unitaire' => 0.2, 'date_echange' => now(), 'statut' => 'propose',
        ]);

        $this->actingAs($acheteur)->delete('/profil', ['password' => 'password'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertModelExists($acheteur);

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete('/profil', ['password' => 'password'])
            ->assertSessionHasErrorsIn('deleteAccount', 'password');
        $this->assertModelExists($admin);
    }
}
