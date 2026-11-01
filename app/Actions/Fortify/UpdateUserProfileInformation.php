<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Nom, e-mail, communauté et photo de profil.
     * Changer d'e-mail remet la vérification à zéro et renvoie un lien.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'communaute_id' => ['nullable', 'exists:communautes,id'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'supprimer_photo' => ['nullable', 'boolean'],
        ])->validateWithBag('updateProfileInformation');

        $this->updatePhoto($user, $input);

        $data = [
            'name' => $input['name'],
            'email' => $input['email'],
            'communaute_id' => $input['communaute_id'] ?? null,
        ];

        if ($input['email'] !== $user->email && $user instanceof MustVerifyEmail) {
            $user->forceFill($data + ['email_verified_at' => null])->save();
            $user->sendEmailVerificationNotification();
        } else {
            $user->forceFill($data)->save();
        }
    }

    /** Les photos sont stockées hors du dossier public et servies par une route (voir UserPhotoController). */
    private function updatePhoto(User $user, array $input): void
    {
        $photo = $input['photo'] ?? null;

        if ($photo instanceof UploadedFile || !empty($input['supprimer_photo'])) {
            if ($user->photo_path) {
                Storage::disk('local')->delete($user->photo_path);
            }
            $user->photo_path = $photo instanceof UploadedFile
                ? $photo->store('photos', 'local')
                : null;
        }
    }
}
