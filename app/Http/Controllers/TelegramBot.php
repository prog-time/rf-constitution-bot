<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\BotUser;
use App\Models\Chapter;

use App\Models\Favorite;
use App\Models\ProgressStudy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramBot extends Controller
{
    private array $dataQuery;
    private array $dataMessage;
    private int $chatID;
    private int $messageID;
    private mixed $callbackID;
    private string $dataValue;
    private mixed $progressStudy;
    private mixed $favoriteData;
    private object $userParams;
    private $lastMessageID;

    public function botController()
    {
        $dataQuery = file_get_contents('php://input');
        // Log::debug($dataQuery);

        if (!empty($dataQuery)) {
            $this->dataQuery = json_decode($dataQuery, true);

            if (!empty($this->dataQuery['message'])) {
                $this->dataMessage = $this->dataQuery['message'];

                $this->chatID = (int)$this->dataQuery['message']['chat']['id'];
                $this->messageID = (int)$this->dataQuery['message']['message_id'];

            } elseif (!empty($this->dataQuery['callback_query'])) {
                $this->dataMessage = $this->dataQuery['callback_query']['message'];
                $this->callbackID = (int)$this->dataQuery['callback_query']['id'];

                $this->chatID = (int)$this->dataMessage['chat']['id'];
                $this->messageID = (int)$this->dataMessage['message_id'];

                $this->dataValue = $this->dataQuery['callback_query']['data'];
            }

            if (!empty($this->chatID)) {
                $this->progressStudy = ProgressStudy::getProgressStudy($this->chatID);
                if (empty($this->progressStudy)) {
                    ProgressStudy::create(
                        [
                            'id_telegram' => $this->chatID,
                            'progress_value' => json_encode([]),
                            'updated_at' => date('d.m.Y H:i'),
                        ]
                    );
                    $this->progressStudy = ProgressStudy::getProgressStudy($this->chatID);
                }

                $this->favoriteData = Favorite::getFavorite($this->chatID);
                if (empty($this->favoriteData)) {
                    Favorite::create(
                        [
                            'id_telegram' => $this->chatID,
                            'favorite_value' => json_encode([]),
                            'updated_at' => date('d.m.Y H:i'),
                        ]
                    );
                    $this->favoriteData = Favorite::getFavorite($this->chatID);
                }

                $userParams = [
                    'id_telegram' => $this->dataMessage['chat']['id'],
                    'firstname' => $this->dataMessage['chat']['first_name'] ?? "",
                    'lastname' => $this->dataMessage['chat']['last_name'] ?? "",
                    'username' => $this->dataMessage['chat']['username'] ?? "",
                ];
                $this->userParams = BotUser::changeUser((int)$this->chatID, $userParams);

                $this->lastMessageID = $this->userParams->id_last_message;

                if (!empty($this->dataQuery['message'])) {
                    $this->messageController();
                } elseif (!empty($this->dataQuery['callback_query'])) {
                    $this->callbackController();
                }
            }
        }

        $this->end();

        die();
    }

    /**
     *
     * @return void
     */
    public function end(): void
    {
        if (!empty($this->lastMessageID)) {
            $lastMessageID = $this->lastMessageID < $this->messageID ? $this->messageID : $this->lastMessageID;
            BotUser::where('id_telegram', $this->chatID)->update([
                'id_last_message' => $lastMessageID,
            ]);
        }
    }

    /**
     * Отправка запроса
     *
     * @param string $method
     * @param array $dataQuery
     * @return void
     */
    private function sendQuery(string $method, array $dataQuery): void
    {
        $resultQuery = TelegramMethods::sendQueryTelegram($method, $dataQuery);
        if (!empty($resultQuery['ok']) && $method !== 'deleteMessage') {
            if (!empty($resultQuery['result']['message_id'])) {
                $this->lastMessageID = $resultQuery['result']['message_id'];
            }
        }
    }

    /**
     * Отправка сообщений со списком глав конституции
     *
     * @return array
     */
    private function listChaptersData(): array
    {
        $listChapters = Chapter::select('*')
            ->orderBy('id')
            ->get();

        $index = 1;
        $keyboardData = [];
        $arrayRow = [];
        foreach ($listChapters as $article) {
            $arrayRow[] = [
                'text' => 'Глава №' . $article->id,
                'callback_data' => 'chapter_' . $article->id,
            ];

            if ($index % 2 == 0) {
                $keyboardData[] = $arrayRow;
                $arrayRow = [];
            }
            if ($index == count($listChapters)) {
                $keyboardData[] = $arrayRow;
            }
            $index++;
        }

        $keyboardData[] = [
            [
                'text' => '🔙 Назад',
                'callback_data' => 'start_menu',
            ]
        ];

        $messageParams = [
            'method' => 'editMessageText',
            'dataQuery' => [
                'chat_id' => $this->chatID,
                'message_id' => $this->messageID,
                'text' => 'Список глав',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboardData
                ]),
            ]
        ];

        return $messageParams;
    }

    /**
     * Список статей добавленных в "Избранное"
     *
     * @return array
     */
    private function listFavoritesData(): array
    {
        $listFavorites = Favorite::getFavorite($this->chatID);
        $favoriteValue = $listFavorites->favorite_value;

        if (!empty($favoriteValue)) {
            $listArticles = Article::select('*')
                ->whereIn('article', $favoriteValue)
                ->get();

            $keyboardData = $this->templateKeyboardArticles($listArticles, 'favorite');

            $keyboardData[] = [
                [
                    'text' => '🔙 Назад',
                    'callback_data' => 'start_menu',
                ]
            ];

            $messageParams = [
                'method' => 'editMessageText',
                'dataQuery' => [
                    'chat_id' => $this->chatID,
                    'message_id' => $this->messageID,
                    'text' => 'Список статей добавленных в "Избранное"',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboardData
                    ]),
                ]
            ];
        } else {
            $keyboardData[] = [
                [
                    'text' => '🔙 Назад',
                    'callback_data' => 'start_menu',
                ]
            ];
            $messageParams = [
                'method' => 'editMessageText',
                'dataQuery' => [
                    'chat_id' => $this->chatID,
                    'message_id' => $this->messageID,
                    'text' => 'Раздел "🌟 Избранное" пуст!',
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboardData
                    ]),
                ]
            ];
        }

        return $messageParams;
    }

    /**
     * Отправка сообщения с текстом статьи
     *
     * @param int $articleID
     * @param string $codeButBack
     * @return array
     */
    private function articleData(int $articleID, string $codeButBack = ""): array
    {
        $articleData = Article::select('*')
            ->where('article', $articleID)
            ->first();

        $messageParams = [];
        if (!empty($articleData)) {
            if (in_array($articleID, $this->progressStudy['progress_value'])) {
                $readButText = "✅  Прочитано";
            } else {
                $readButText = "☑️  Прочитано";
            }
            $keyboardData[] = [
                [
                    'text' => $readButText,
                    'callback_data' => 'set_status_read_' . $articleData->article,
                ]
            ];

            if (in_array($articleID, $this->favoriteData['favorite_value'])) {
                $favoriteButText = "⭐️ Добавлено в избранное";
            } else {
                $favoriteButText = "Добавить в избранное";
            }
            $keyboardData[] = [
                [
                    'text' => $favoriteButText,
                    'callback_data' => 'set_status_favorite_' . $articleData->article,
                ]
            ];

            if ((int)$articleData->article > 1) {
                $prevID = (int)$articleData->article - 1;
                $rowNavigation[] = [
                    'text' => '⬅️',
                    'callback_data' => 'article_' . $prevID,
                ];
            }

            if ((int)$articleData->article < 137) {
                $nextID = (int)$articleData->article + 1;
                $rowNavigation[] = [
                    'text' => '➡️',
                    'callback_data' => 'article_' . $nextID,
                ];
            }

            $keyboardData[] = $rowNavigation;

            if (empty($codeButBack)) {
                $codeButBack = 'chapter_' . $articleData->chapter;
            }

            $keyboardData[] = [
                [
                    'text' => '🔙 Назад',
                    'callback_data' => $codeButBack,
                ]
            ];

            $textMessage = "Статья №" . $articleData->article;
            $textMessage .= "\n\n";
            $textMessage .= $articleData->text;

            $messageParams = [
                'method' => 'editMessageText',
                'dataQuery' => [
                    'chat_id' => $this->chatID,
                    'message_id' => $this->messageID,
                    'text' => $textMessage,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboardData
                    ]),
                ]
            ];
        }

        return $messageParams;
    }

    /**
     * Изменение статуса прочтения статьи
     *
     * @param int $articleID
     * @return void
     */
    private function setReadArticle(int $articleID): void
    {
        $progressStudy = ProgressStudy::changeProgressStudy((int)$this->chatID, (int)$articleID);

        if (empty($progressStudy->progress_value)) {
            $progressStudy->progress_value = [];
        }

        $this->progressStudy['progress_value'] = $progressStudy->progress_value;

        $queryParams = $this->articleData($articleID);
        if (!empty($queryParams)) {
            $this->sendQuery('editMessageText', $queryParams['dataQuery']);
        }

        $callbackParams = [
            'callback_query_id' => $this->callbackID,
            'chat_id' => $this->chatID,
        ];
        if (in_array($articleID, $this->progressStudy['progress_value'])) {
            $callbackParams['text'] = 'Статья добавлена в прочитанное';
        } else {
            $callbackParams['text'] = 'Статья убрана из прочитанного';
        }
        $this->sendQuery('answerCallbackQuery', $callbackParams);
    }

    /**
     * Изменение статуса "Избранное" статьи
     *
     * @param int $articleID
     * @return void
     */
    private function setFavoriteArticle(int $articleID)
    {
        $favoriteData = Favorite::changeFavorite((int)$this->chatID, (int)$articleID);

        if (!empty($favoriteData)) {
            $this->favoriteData['favorite_value'] = $favoriteData->favorite_value;

            $queryParams = $this->articleData($articleID);
            if (!empty($queryParams)) {
                $this->sendQuery('editMessageText', $queryParams['dataQuery']);
            }

            $callbackParams = [
                'callback_query_id' => $this->callbackID,
                'chat_id' => $this->chatID,
            ];
            if (in_array($articleID, $this->favoriteData['favorite_value'])) {
                $callbackParams['text'] = 'Добавлено в "избранное"';
            } else {
                $callbackParams['text'] = 'Удалено из раздела "избранное"';
            }
            $this->sendQuery('answerCallbackQuery', $callbackParams);
        }
    }

    /**
     * Формирование клавиатуры для сообщения со статьёй
     *
     * @param object $listArticles - список статей
     * @param string $postfixArticleCode - добавление code к статье
     * @return array
     */
    private function templateKeyboardArticles(object $listArticles, string $postfixArticleCode = ""): array
    {
        $index = 1;
        $keyboardData = [];
        $arrayRow = [];
        foreach ($listArticles as $article) {
            if (in_array($article->article, $this->progressStudy['progress_value'])) {
                $articleButText = "✅ " . $article->article;
            } else {
                $articleButText = "☑️ " . $article->article;
            }

            $arrayRow[] = [
                'text' => $articleButText,
                'callback_data' => 'article_' . $article->id . ((!empty($postfixArticleCode)) ? '_' . $postfixArticleCode : ""),
            ];

            if ($index % 4 == 0) {
                $keyboardData[] = $arrayRow;
                $arrayRow = [];
            }
            if ($index == count($listArticles)) {
                $keyboardData[] = $arrayRow;
            }
            $index++;
        }

        return $keyboardData;
    }

    /**
     * Список статей
     *
     * @param int $chapterID
     * @return array
     */
    private function listArticlesData(int $chapterID): array
    {
        $listArticles = Article::select('*')
            ->where('chapter', $chapterID)
            ->orderBy('id')
            ->get();

        $keyboardData = $this->templateKeyboardArticles($listArticles);

        $keyboardData[] = [
            [
                'text' => '🔙 Назад',
                'callback_data' => 'chapters',
            ]
        ];

        $messageParams = [
            'method' => 'editMessageText',
            'dataQuery' => [
                'chat_id' => $this->chatID,
                'message_id' => $this->messageID,
                'text' => 'Глава №' . $chapterID . ': список статей',
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboardData
                ]),
            ]
        ];

        return $messageParams;
    }

    /**
     * Изменение статуса "Прочитано"
     *
     * @return void
     */
    private function changeStatusStudy(): void
    {
        if (!empty($this->userParams->study_status)) {
            $newStatus = 0;
        } else {
            $newStatus = 1;
        }

        $userParams = [
            'study_status' => $newStatus,
        ];
        $this->userParams = BotUser::changeUser((int)$this->chatID, $userParams);

        $queryParams = $this->getStudyMenu();
        $this->sendQuery('editMessageText', $queryParams);
    }

    /**
     * Генерация сообщения при нажатии на "Прочитано"
     *
     * @return array
     */
    private function getStudyMenu(): array
    {
        $textMessage = $this->textMyProgress();
        $textStatusStudy = (!empty($this->userParams->study_status)) ? '⛔️ Закончить обучение' : '👨🏼‍🎓 Начать обучение';
        $messageParams = [
            'chat_id' => $this->chatID,
            'message_id' => $this->messageID,
            'parse_mode' => 'html',
            'text' => $textMessage,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => $textStatusStudy,
                            'callback_data' => 'study_change_status',
                        ],
                    ],
                    [
                        [
                            'text' => '🔙 Назад',
                            'callback_data' => 'start_menu',
                        ],
                    ],
                ],
            ]),
        ];

        return $messageParams;
    }

    /**
     * Текст сообщения прогресса обучения
     *
     * @return string
     */
    private function textMyProgress(): string
    {
        $textMessage = "<b>🎓 Мой прогресс обучения</b> \n";

        $progressValue = $this->progressStudy->progress_value;
        if (!empty($progressValue)) {
            $procentStudy = round(count($progressValue) / 137 * 100);

            if ($procentStudy < 20) {
                $textMessage .= "Уровень: школьник \n";
            } elseif ($procentStudy >= 20 && $procentStudy < 50) {
                $textMessage .= "Уровень: понимающий \n";
            } elseif ($procentStudy >= 50 && $procentStudy < 100) {
                $textMessage .= "Уровень: знаток \n";
            } elseif ($procentStudy == 100) {
                $textMessage .= "Уровень: знаток \n";
            }

            $textMessage .= "Прогресс: " . count($progressValue) . "/137 статей \n";
        } else {
            $textMessage .= "Уровень: школьник \n";
            $textMessage .= "Прогресс: 0/137 статей \n";
        }

        $textMessage .= "\n";
        $textMessage .= "<b>📖 Начать обучение</b>: \n";
        $textMessage .= "Бот будет присылать новые статьи на изучение каждый день";

        return $textMessage;
    }

    /**
     * Обработка нажатия на команды
     *
     * @return void
     */
    private function callbackController(): void
    {
        if (!empty($this->dataQuery)) {
            $arParams = explode('_', $this->dataValue);

            switch ($this->dataValue) {
                /* список глав */
                case 'start_menu':
                    $this->sendQuery('editMessageText', $this->getStartMenu(true));
                    break;

                case 'study_change_status':
                    $this->changeStatusStudy();
                    break;

                case 'chapters':
                    $queryParams[] = $this->listChaptersData();
                    break;

                case 'favorites':
                    $queryParams[] = $this->listFavoritesData();
                    break;

                case 'study':
                    $this->sendQuery('editMessageText', $this->getStudyMenu());
                    break;

                case preg_match('/(chapter_)/', $this->dataValue) == 1:
                    $idChapter = $arParams[1];

                    $queryParams[] = $this->listArticlesData((int)$idChapter);

                    break;

                case preg_match('/(article_)/', $this->dataValue) == 1:
                    $idArticle = $arParams[1];

                    $codeBut = "";
                    if (!empty($arParams[2])) {
                        $dopCodeArticle = $arParams[2];
                        $codeBut = ($dopCodeArticle == 'favorite') ? 'favorites' : '';
                    }

                    $queryParams[] = $this->articleData($idArticle, $codeBut);

                    break;

                case preg_match('/(set_status_read_)/', $this->dataValue) == 1:
                    $idArticle = $arParams[3];
                    $this->setReadArticle((int)$idArticle);

                    break;

                case preg_match('/(set_status_favorite_)/', $this->dataValue) == 1:
                    $idArticle = $arParams[3];
                    $this->setFavoriteArticle($idArticle);

                    break;
            }

            if (!empty($queryParams)) {
                foreach ($queryParams as $param) {
                    if (!empty($param['method']) && !empty($param['dataQuery'])) {
                        $this->sendQuery($param['method'], $param['dataQuery']);
                    }
                }
            }

        }
    }

    /**
     * Генерация сообщения для команды help
     *
     * @return array
     */
    private function getHelpMessage(): array
    {
        $textMessage = "*Базовые команды для работы с ботом*: \n\n";
        $textMessage .= "▶️ /start – запуск/перезапуск бота \n\n";
        $textMessage .= "▶️ /help – описание команд \n\n";
        $textMessage .= "📃 /articles – показать список разделов бота \n\n";
        $textMessage .= "📃 `/article_{номер_статьи}` – открыть статью по номеру \n\n";
        $textMessage .= "👨🏼‍🎓 /study – открыть раздел обучения \n\n";
        $textMessage .= "🌟 /favorites – открыть раздел \"Избранное\" \n\n";

        $textMessage .= "Данный проект был полностью разработан командой *Prog-Time*";

        $messageParams = [
            'chat_id' => $this->chatID,
            'text' => $textMessage,
            'parse_mode' => 'MARKDOWN',
            'message_id' => $this->messageID,
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => 'Перейти на сайт автора',
                            'url' => 'https://prog-time.ru',
                        ],
                    ],
                    [
                        [
                            'text' => '🏘 На главную',
                            'callback_data' => 'start_menu',
                        ],
                    ],
                ],
            ]),
        ];

        return $messageParams;
    }

    /**
     * Обработка текстовых команд
     *
     * @return void
     */
    private function messageController(): void
    {
        if (!empty($this->dataMessage)) {
            $this->sendQuery('deleteMessage', [
                'chat_id' => $this->chatID,
                'message_id' => $this->lastMessageID
            ]);

            switch ($this->dataMessage['text']) {
                case '/start':
                    $this->sendQuery('sendMessage', $this->getStartMenu());
                    break;

                case '/articles':
                    $queryParams = $this->listChaptersData();
                    $this->sendQuery('sendMessage', $queryParams['dataQuery']);
                    break;

                case '/favorites':
                    $queryParams = $this->listFavoritesData();
                    $this->sendQuery('sendMessage', $queryParams['dataQuery']);
                    break;

                case '/study':
                    $this->sendQuery('sendMessage', $this->getStudyMenu());
                    break;

                case '/help':
                    $this->sendQuery('sendMessage', $this->getHelpMessage());
                    break;
            }

            $this->sendQuery('deleteMessage', [
                'chat_id' => $this->chatID,
                'message_id' => $this->messageID
            ]);
        }
    }

    /**
     * Генерация стартового сообщения
     *
     * @param bool $editStatus - статус редактирования
     * @return array
     */
    private function getStartMenu(bool $editStatus = false): array
    {
        $textMessage = "С помощью данного бота вы можете изучить статьи «Конституции РФ». \n\n";
        $textMessage .= "<b>Разделы бота</b>: \n";
        $textMessage .= "📃 <b>Статьи</b> – основной раздел, где хранятся статьи разбитые на главы \n\n";
        $textMessage .= "👨🏼‍🎓 <b>Прогресс изучения</b> – информация о статусе изучения Конституции РФ \n\n";
        $textMessage .= "🌟 <b>Избранное</b> – список статей добавленных в избранное \n\n";

        $messageParams = [
            'chat_id' => $this->chatID,
            'text' => $textMessage,
            'parse_mode' => 'html',
            'reply_markup' => json_encode([
                'inline_keyboard' => [
                    [
                        [
                            'text' => '📃 Статьи',
                            'callback_data' => 'chapters',
                        ],
                    ],
                    [
                        [
                            'text' => '👨🏼‍🎓 Прогресс обучения',
                            'callback_data' => 'study',
                        ],
                    ],
                    [
                        [
                            'text' => '🌟 Избранное',
                            'callback_data' => 'favorites',
                        ],
                    ],
                ],
            ]),
        ];

        if ($editStatus) {
            $messageParams['message_id'] = $this->messageID;
        }

        return $messageParams;
    }

}
