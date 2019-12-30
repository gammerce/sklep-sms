<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PageActionBoxResource
{
    public function get(
        $pageId,
        $actionBoxId,
        Request $request,
        TranslationManager $translationManager,
        Heart $heart
    ) {
        $lang = $translationManager->user();

        if (!isset($pageId) || !isset($actionBoxId)) {
            return new ApiResponse("no_data", $lang->translate('not_all_data'), 0);
        }

        if (($page = $heart->getPage($pageId, "admin")) === null) {
            return new ApiResponse("wrong_page", $lang->translate('wrong_page_id'), 0);
        }

        if (!($page instanceof IPageAdminActionBox)) {
            return new ApiResponse(
                "page_no_action_box",
                $lang->translate('no_action_box_support'),
                0
            );
        }

        $actionBox = $page->getActionBox($actionBoxId, $request->query->all());

        $data = [];
        if (strlen($actionBox['template'])) {
            $data['template'] = $actionBox['template'];
        }

        return new ApiResponse($actionBox['status'], array_get($actionBox, 'text'), true, $data);
    }
}
