<?php
namespace App\ServiceModules\Interfaces;

use App\Http\Validation\Validator;

/**
 * Support for adding new services in ACP
 */
interface IServiceAdminManage
{
    /**
     * Method called when editing or adding a service in ACP
     * Should return additional fields to be filled
     *
     * @return string
     */
    public function serviceAdminExtraFieldsGet(): string;

    /**
     * The method tests the data submitted using the form when adding a new service in ACP
     *
     * @param Validator $validator
     */
    public function serviceAdminManagePre(Validator $validator): void;

    /**
     * Called after verification of
     * data sent in the form for adding a new service in ACP went smoothly
     *
     * @param array $body
     * @return array
     */
    public function serviceAdminManagePost(array $body): array;
}
