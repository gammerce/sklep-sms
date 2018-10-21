<?php
namespace App\Middlewares;

use App\Application;
use App\Auth;
use App\Exceptions\InvalidResponse;
use App\Exceptions\RequestException;
use App\License;
use App\Models\User;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseIsValid implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var License $license */
        $license = $app->make(License::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $app->make(TranslationManager::class);
        $lang = $translationManager->user();

        /** @var Auth $auth */
        $auth = $app->make(Auth::class);
        $user = $auth->user();

        try {
            $license->validate();
        } catch (RequestException $e) {
            return new Response($lang->translate('verification_error'));
        } catch (InvalidResponse $e) {
            $this->limitPrivileges($user);

            if (SCRIPT_NAME == "index") {
                return $this->renderErrorPage($e->response);
            }

            if (in_array(SCRIPT_NAME, ["jsonhttp", "servers_stuff", "extra_stuff"])) {
                return new Response();
            }
        }

        return null;
    }

    private function limitPrivileges(User $user)
    {
        if (get_privilages("manage_settings", $user)) {
            $user->removePrivilages();
            $user->setPrivilages([
                "acp"             => true,
                "manage_settings" => true,
            ]);
        }
    }

    protected function renderErrorPage(\App\Requesting\Response $response)
    {
        // TODO Implement it to be prettier
        return new Response($response->json());
    }
}