<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Les rôles passent de la colonne users.role à Spatie Permission (rôles + permissions).
 * Les rôles et permissions sont créés ici (et non dans un seeder) pour exister dans toute base migrée.
 */
return new class extends Migration
{
    /** Permissions par rôle. L'admin a tout. */
    private const PERMISSIONS = [
        'gerer-utilisateurs' => ['admin'],
        'gerer-communautes' => ['admin'],
        'voir-toutes-les-donnees' => ['admin'],
        'publier-offres' => ['admin', 'membre'],
        'acheter-energie' => ['admin', 'membre'],
        'gerer-compteurs' => ['admin', 'membre'],
        'utiliser-ia' => ['admin', 'membre'],
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = collect(['admin', 'membre'])->mapWithKeys(fn ($r) => [$r => Role::findOrCreate($r, 'web')]);

        foreach (self::PERMISSIONS as $permission => $roleNames) {
            Permission::findOrCreate($permission, 'web');
            foreach ($roleNames as $r) {
                $roles[$r]->givePermissionTo($permission);
            }
        }

        // Reprise des rôles existants
        if (Schema::hasColumn('users', 'role')) {
            foreach (DB::table('users')->select('id', 'role')->get() as $user) {
                DB::table('model_has_roles')->insertOrIgnore([
                    'role_id' => $roles[$user->role ?: 'membre']->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                ]);
            }

            Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'membre'])->default('membre')->after('password');
        });

        $adminId = DB::table('roles')->where('name', 'admin')->value('id');
        $admins = DB::table('model_has_roles')->where('role_id', $adminId)->pluck('model_id');
        DB::table('users')->whereIn('id', $admins)->update(['role' => 'admin']);
    }
};
