<?php

    /**
     * @api {post} /mobile/cctv/recPrepare запросить фрагмент архива
     * @apiVersion 1.0.0
     * @apiDescription **почти готов**
     *
     * @apiGroup CCTV
     *
     * @apiBody {Number} id идентификатор камеры
     * @apiBody {String="Y-m-d H:i:s"} from начало фрагмента
     * @apiBody {String="Y-m-d H:i:s"} to конец фрагмента
     *
     * @apiHeader {String} authorization токен авторизации
     *
     * @apiSuccess {Number} - идентификатор фрагмента
     */

    auth();

    $cameraId = (int)@$postdata['id'];
    $households = loadBackend("households");
    $cameras = loadBackend("cameras");

    // check if the user has access to the camera
    $hasAccess = false;
    foreach($subscriber['flats'] as $flat) {
        $houseId = $flat['addressHouseId'];
        $flatDetail = $households->getFlat($flat['flatId']);
        $flatIsBlocked = $flatDetail['adminBlock'] || $flatDetail['manualBlock'] || $flatDetail['autoBlock'];
        if ($flatIsBlocked)
            continue;

        $checkCamera = function ($cameras, $cameraId) {
            foreach ($cameras as $camera)
                if ($camera['cameraId'] == $cameraId)
                    return true;

            return false;
        };

        // get cameras attached to the house
        $cameras = $households->getCameras("houseId", $houseId);
        if ($checkCamera($cameras, $cameraId)) {
            $hasAccess = true;
            break;
        }

        // get cameras attached to the flat
        $cameras = $households->getCameras("flatId", $flat['flatId']);
        if ($checkCamera($cameras, $cameraId)) {
            $hasAccess = true;
            break;
        }

        // check cameras attached to the entrances of the flat
        foreach ($flatDetail['entrances'] as $entrance) {
            $e = $households->getEntrance($entrance['entranceId']);
            if ($e['cameraId'] == $cameraId) {
                $hasAccess = true;
                break;
            }
        }
    }

    // приложение везде при работе с архивом передаёт время по часовому поясу Москвы, если не в конфиге это не переопределено.

    if (@$config["mobile"]["time_zone"]) {
        date_default_timezone_set($config["mobile"]["time_zone"]);
    } else {
        date_default_timezone_set('Europe/Moscow');
    }

    $from = strtotime(@$postdata['from']);
    $to = strtotime(@$postdata['to']);

    if (!$cameraId || !$from || !$to || !$hasAccess) {
        response(404);
    }

    $dvrExports = loadBackend("dvrExports");

    // проверяем, не был ли уже запрошен данный кусок из архива.
    $check = $dvrExports->checkDownloadRecord($cameraId, $subscriber["subscriberId"], $from, $to);
    if (@$check['id']) {
        response(200, $check['id']);
    }

    // если такой кусок ещё не запрашивали, то добавляем запрос на скачивание.
    $res = (int)$dvrExports->addDownloadRecord($cameraId, $subscriber["subscriberId"], $from, $to);
    session_write_close();
    exec("php ". __DIR__."/../../cli.php dvrExports --run-record-download=$res >/dev/null 2>/dev/null &");

    response(200, $res);
