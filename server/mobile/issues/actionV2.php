<?php

    /**
     * @api {post} /mobile/issues/actionV2 выполнить переход
     * @apiDescription **нет проверки на принадлежность заявки именно этому абоненту**
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} key номер заявки
     * @apiBody {String="close","changeQRDeliveryType"} action действие
     * @apiBody {String="office","courier"} [deliveryType] способ доставки
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issueAdapter');
    if (!$adapter)
        response(417, false, false, i18n("mobile.cantChangeIssue"));

    $result = $adapter->actionIssue($postdata);
    if ($result === false)
        response(417, false, false, i18n("mobile.cantChangeIssue"));

    response();
