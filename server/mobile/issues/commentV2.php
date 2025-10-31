<?php

    /**
     * @api {post} /mobile/issues/commentV2 оставить комментарий в заявке
     * @apiDescription нет проверки на принадлежность заявки именно этому абоненту**
     * @apiVersion 1.0.0
     * @apiGroup Issues
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiBody {String} key номер заявки
     * @apiBody {String} comment комментарий
     *
     * @apiErrorExample Ошибки
     * 422 неверный формат данных
     * 417 ожидание не удалось
     */

    auth();

    $adapter = loadBackend('issueAdapter');

    if (!$adapter) {
        response();
    }

    $adapter->commentIssue(@$postdata['key'], @$postdata['comment']);

    response();
