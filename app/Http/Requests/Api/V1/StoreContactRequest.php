<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\StoreContactRequest as WebStoreContactRequest;

class StoreContactRequest extends WebStoreContactRequest
{
    /**
     * APIは電話番号を tel で直接受け取るため、入力ページ用の結合処理は行わない
     */
    protected function prepareForValidation(): void {}
}
