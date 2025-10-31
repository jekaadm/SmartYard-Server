<?php

// works with the first(default) version of issues

namespace backends\issue_adapter {

    class lanta extends issue_adapter {
        /*
            {
                "issue": {
                    "description": "Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры Видовая / Чичерина 9/2 (id = 2621) по  парамертам: дата: 12.09.2023, время: 12-59, продолжительность фрагмента: 10 минут. Комментарий  пользователя: test123.",
                    "project": "REM",
                    "summary": "Авто: Запрос на получение видеофрагмента с  архива",
                    "type": 32
                },
                "customFields": {
                    "10011": "-5",
                    "11840": "12.09.23 12:09",
                    "12440": "Приложение"
                },
                "actions": [
                    "Начать работу",
                    "Менеджеру ВН"
                ]
            }
        */

        private function createIssueForDVRFragment($phone, $description, $camera_id, $datetime, $duration, $comment) {
            /*
             {
                "project": "RTL",
                "query": {
                    "catalog": {"$regex": "^\\[9002\\].*"}
                },
                "sortBy": {"created": -1},
                "limit": 1,
                "fields": true
             }

             */

            $prev_issue = $this->getLastOpenedIssue($phone, 9002, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $cam_id = 900000000 + $this->extractCameraId($description);
            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9002] Запрос на получение видеофрагмента",
                            "subject" => "Запрос на получение видеофрагмента",
                            "assigned" => "cctv",
                            "_cf_object_id" => strval($cam_id),
                            "_cf_phone" => $phone,
                            "description" => $description,
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        private function createIssueCallback($phone)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9005] Исходящий звонок",
                            "subject" => "Исходящий звонок",
                            "assigned" => "callcenter",
                            "_cf_phone" => $phone,
                            "description" => "Выполнить звонок клиенту по запросу из приложения.",
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        private function createIssueForgotEverything($phone)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode([
                        "issue" => [
                            "project" => "RTL",
                            "workflow" => "lanta",
                            "catalog" => "[9005] Исходящий звонок",
                            "subject" => "Исходящий звонок",
                            "assigned" => "callcenter",
                            "_cf_phone" => $phone,
                            "description" => "Выполнить звонок клиенту для напоминания номера договора и пароля от личного кабинета.",
                        ],
                    ]),
                ],
            ])), true);

            if (empty($issue['id']))
                return ['isNew' => false, 'issueId' => null];
            else
                return ['isNew' => true, 'issueId' => $issue['id']];
        }

        private function createIssueConfirmAddress($phone, $description, $name, $address, $lat, $lon)
        {
            $params = $this->extractValuesForConfirmAddress($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";

            $prev_issue = $this->getLastOpenedIssue($phone, 9007, $this->config["backends"]["issue_adapter"]["anti_spam_interval"], $address);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9007] Нужен код доступа",
                    "subject" => "Нужен код доступа",
                    "assigned" => "office",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введённый пользователем: $address\n"
                        . "Подготовить конверт с qr-кодом. Далее заявку отправить курьеру."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        private function createIssueDeleteAddress($phone, $description, $name, $address, $lat, $lon, $reason)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9005, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForDeleteAddress($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $reason = $params['reason'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9005] Исходящий звонок",
                    "subject" => "Исходящий звонок",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введённый пользователем: $address\n"
                        . "Удаление адреса из приложения. Причина: $reason"
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        private function createIssueUnavailableServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9001, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForUnavailableServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9001] Заявка из приложения",
                    "subject" => "Заявка из приложения",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введённый пользователем: $address\n"
                        . "Список подключаемых услуг: $services"
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueAvailableWithSharedServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9006, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForAvailableWithSharedServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9006] Подключение",
                    "subject" => "Подключение",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введённый пользователем: $address\n"
                        . "Список подключаемых услуг: $services\n"
                        . "Требуется подтверждение адреса и подключение выбранных услуг."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function createIssueAvailableWithoutSharedServices($phone, $description, $name, $address, $lat, $lon, $services)
        {
            $prev_issue = $this->getLastOpenedIssue($phone, 9006, $this->config["backends"]["issue_adapter"]["anti_spam_interval"]);
            if ($prev_issue !== false)
                return ['isNew' => false, 'issueId' => $prev_issue['issueId']];

            $params = $this->extractValuesForAvailableWithoutSharedServices($description);
            $name = $params['name'] ?? "";
            $address = $params['address'] ?? "";
            $services = $params['services'] ?? "";
            $content = [
                "issue" => [
                    "project" => "RTL",
                    "workflow" => "lanta",
                    "catalog" => "[9006] Подключение",
                    "subject" => "Подключение",
                    "assigned" => "callcenter",
                    "_cf_phone" => $phone,
                    "description" => "ФИО: $name\n"
                        . "Адрес, введённый пользователем: $address\n"
                        . "Список подключаемых услуг: $services\n"
                        . "Выполнить звонок клиенту и осуществить консультацию."
                ],
            ];
            return $this->createLantaIssue($lat, $lon, $content);
        }

        public function listConnectIssues($phone)
        {
            $content = [
                "project" => "RTL",
                "query" => [
                    'description' => ['$regex' => "Адрес, введ[её]нный пользователем"],
                    "_cf_phone" => $phone,
                    "status" => "Открыта",
                    "catalog" => ['$regex' => "^\\[9007\\].*"]
                ],
                "sortBy" => ["created" => 1],
                "fields" => ["issueId", "description"]
            ];

            $result = json_decode(file_get_contents($this->tt_url . "/issues", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            if (!isset($result['issues']['issues']))
                return false;

            $issues = [];
            foreach ($result['issues']['issues'] as $issue) {
                $r = [];
                $r['key'] = $issue['issueId'];
                $description = $issue['description'];
                $address = $this->extractAddress($description)['address'] ?? false;
                if ($address !== false)
                    $r['address'] = $address;
                $courier = strpos($description, 'курьер') !== false ? "t" : "f";
                $r['courier'] = $courier;
                $services = $this->extractServices($description)['services'] ?? false;

                if ($services !== false)
                    $r['services'] = $services;
                $issues[] = $r;
            }
            return $issues;
        }

        public function commentIssue($issueId, $comment)
        {
            $content = [
                "issueId" => $issueId,
                "comment" => $comment,
                "commentPrivate" => false,
                "type" => false
            ];

            $result = json_decode(file_get_contents($this->tt_url . "/comment", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            return $result[0] ?? false;
        }

        public function closeIssue($issueId)
        {
            $content = [
                "action" => "Закрыть"
            ];

            $result = json_decode(file_get_contents($this->tt_url . "/action/" . $issueId, false, stream_context_create([
                "http" => [
                    "method" => "PUT",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            return $result[0] ?? false;
        }

        private function extractCameraId($input_string) {
            $pattern = '/\(id\s*=\s*(\d+)\)/';
            if (preg_match($pattern, $input_string, $matches)) {
                return intval($matches[1]);
            }
            return 0;
        }

        private function extractAddress($input_string) {
            $pattern = '/Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*(?:$|\n)/su';
            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractServices($input_string) {
            $pattern = '/Список подключаемых услуг:\s*(?<services>.*?)\s*(?:$|\n)/su';
            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForConfirmAddress($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\s/';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*)?Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*\n/su';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForDeleteAddress($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sПричина:\s*(?<reason>.*\S|)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*Причина:\s*(?<reason>.*?)\s*$/su';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForUnavailableServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sСписок подключаемых услуг:\s*(?<services>.*\S|)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*Список подключаемых услуг:\s*(?<services>.*?)\s*$/su';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForAvailableWithSharedServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*|)\n(Список подключаемых услуг:\s*)?\s*(?<services>.*)\nТребуется подтверждение адреса/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*Список подключаемых услуг:\s*(?<services>.*?)\s*Требуется подтверждение адреса\s*/su';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        private function extractValuesForAvailableWithoutSharedServices($input_string) {
            // old
            // $pattern = '/ФИО:\s*(?<name>.*\S|)\s*(?<phone>Телефон\s*)?Адрес, введённый пользователем:\s*(?<address>.*\S|)\sПодключение услуг\(и\):\s*(?<services>[^\n]*\n)/s';

            // new
            $pattern = '/ФИО:\s*(?<name>.*?)\s*(?:\n|Телефон:\s*.*?)Адрес, введ[её]нный пользователем:\s*(?<address>.*?)\.?\s*Подключение услуг\(и\):\s*(?<services>.*?)\s*$/su';

            if (preg_match($pattern, $input_string, $matches)) {
                return $matches;
            }
            return false;
        }

        /**
         * @param $lat
         * @param $lon
         * @param array $content
         * @return mixed
         */
        private function createLantaIssue($lat, $lon, array $content)
        {
            if (isset($lat) && isset($lon)) {
                $cf_geo = [
                    "type" => "Point",
                    "coordinates" => [floatval(str_replace(',', '.', $lon)),
                        floatval(str_replace(',', '.', $lat))]];
                $content['issue']['_cf_geo'] = $cf_geo;
            }

            $issue = json_decode(file_get_contents($this->tt_url . "/issue", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            if (isset($issue['id']))
                return ['isNew' => true, 'issueId' => $issue['id']];
            else
                return ['isNew' => false, 'issueId' => null];
        }

        private function getLastOpenedIssue($phone, $catalog, $anti_spam_interval, $address = null)
        {
            $content = [
                "project" => "RTL",
                "query" => [
                    "catalog" => ['$regex' => "^\\[$catalog\\].*"],
                    "_cf_phone" => $phone,
                    "status" => "Открыта"
                ],
                "sortBy" => ["created" => -1],
                "limit" => 1,
                "fields" => ["issueId", "created"]
            ];
            if (isset($anti_spam_interval) && $anti_spam_interval > 0) {
                $content['query']['created'] = ['$gt' => time() - $anti_spam_interval];
            }
            if (!empty($address)) {
                $content['query']['description'] = ['$regex' => "\\Q$address\\E"];
            }

            $result = json_decode(file_get_contents($this->tt_url . "/issues", false, stream_context_create([
                "http" => [
                    "method" => "POST",
                    "header" => [
                        "Content-Type: application/json; charset=utf-8",
                        "Accept: application/json; charset=utf-8",
                        "Authorization: Bearer $this->tt_token",
                    ],
                    "content" => json_encode($content),
                ],
            ])), true);

            return $result['issues']['issues'][0] ?? false;
        }

        public function createIssue($phone, $data)
        {
            $description = $data['issue']['description'];
            $summary = $data['issue']['summary'];

            if (strpos($description, 'Обработать запрос на добавление видеофрагмента из архива видовой видеокамеры') !== false) {
                return $this->createIssueForDVRFragment($phone, $description, null, null, null, null);
            } elseif (strpos($summary, 'Авто: Звонок с приложения') !== false) {
                if (strpos($description, 'Выполнить звонок клиенту по запросу с приложения') !== false
                    || strpos($description, 'Выполнить звонок клиенту по запросу из приложения') !== false)
                    return $this->createIssueCallback($phone);
                elseif (strpos($description, 'Выполнить звонок клиенту для напоминания номера договора') !== false)
                    return $this->createIssueForgotEverything($phone);
            } elseif (strpos($description, 'Подготовить конверт') !== false) {
                $lat = $data['customFields']['10743'];
                $lon = $data['customFields']['10744'];
                return $this->createIssueConfirmAddress($phone, $description, null, null, $lat, $lon);
            } elseif (strpos($description, 'Удаление адреса') !== false) {
                $lat = $data['customFields']['10743'];
                $lon = $data['customFields']['10744'];
                return $this->createIssueDeleteAddress($phone, $description, null, null, $lat, $lon, null);
            } elseif (strpos($description, 'Список подключаемых услуг') !== false
                && strpos($description, 'Требуется подтверждение адреса') === false) {
                $lat = $data['customFields']['10743'];
                $lon = $data['customFields']['10744'];
                return $this->createIssueUnavailableServices($phone, $description, null, null, $lat, $lon, null);
            } elseif (strpos($description, 'Выполнить звонок клиенту') !== false) {
                $lat = $data['customFields']['10743'];
                $lon = $data['customFields']['10744'];
                return $this->createIssueAvailableWithoutSharedServices($phone, $description, null, null, $lat, $lon, null);
            }

            return false;
        }

        public function actionIssue($data)
        {
            $issueId = @$data['key'];
            $action = @$data['action'];
            $customFields = @$data['customFields'];
            if ($action === "Jelly.Закрыть авто")
                return $this->closeIssue($issueId)[0] ?? false;

            if ($action === "Jelly.Способ доставки") {
                $is_courier = true;
                foreach ($customFields as $cf) {
                    if ($cf['number'] === '10941' && $cf['value'] !== 'Курьер')
                        $is_courier = false;
                }

                if (!$is_courier)
                    return $this->closeIssue($issueId)[0] ?? false;

                return true;
            }

            return false;
        }
    }
}
