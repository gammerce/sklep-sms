<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\Rules\FullNameRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\PostalCodeRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\SteamIdRule;
use App\Http\Validation\Rules\UniqueSteamIdRule;
use App\Http\Validation\Rules\UniqueUsernameRule;
use App\Http\Validation\Rules\UsernameRule;
use App\Http\Validation\Validator;
use App\Payment\General\BillingAddress;
use App\Repositories\UserRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class UserProfileResource
{
    public function put(
        Request $request,
        TranslationManager $translationManager,
        UserRepository $userRepository,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $validator = new Validator(
            [
                "username" => trim($request->request->get("username") ?? ""),
                "forename" => trim($request->request->get("forename") ?? ""),
                "surname" => trim($request->request->get("surname") ?? ""),
                "steam_id" => trim($request->request->get("steam_id") ?? ""),
                "billing_address_name" => trim(
                    $request->request->get("billing_address_name") ?? ""
                ),
                "billing_address_vat_id" => trim(
                    $request->request->get("billing_address_vat_id", "")
                ),
                "billing_address_street" => trim(
                    $request->request->get("billing_address_street", "")
                ),
                "billing_address_postal_code" => trim(
                    $request->request->get("billing_address_postal_code", "")
                ),
                "billing_address_city" => trim(
                    $request->request->get("billing_address_city") ?? ""
                ),
            ],
            [
                "username" => [
                    new RequiredRule(),
                    new UsernameRule(),
                    new UniqueUsernameRule($user->getId()),
                ],
                "forename" => [],
                "surname" => [],
                "steam_id" => [new SteamIdRule(), new UniqueSteamIdRule($user->getId())],
                "billing_address_name" => [new FullNameRule(), new MaxLengthRule(128)],
                "billing_address_vat_id" => [new MaxLengthRule(128)],
                "billing_address_street" => [new MaxLengthRule(128)],
                "billing_address_postal_code" => [new PostalCodeRule(), new MaxLengthRule(128)],
                "billing_address_city" => [new MaxLengthRule(128)],
            ]
        );

        $validated = $validator->validateOrFail();

        $user->setUsername($validated["username"]);
        $user->setForename($validated["forename"]);
        $user->setSurname($validated["surname"]);
        $user->setSteamId($validated["steam_id"]);
        $user->setBillingAddress(
            new BillingAddress(
                $validated["billing_address_name"],
                $validated["billing_address_vat_id"],
                $validated["billing_address_street"],
                $validated["billing_address_postal_code"],
                $validated["billing_address_city"]
            )
        );

        $userRepository->update($user);

        return new SuccessApiResponse($lang->t("profile_edit"));
    }
}
