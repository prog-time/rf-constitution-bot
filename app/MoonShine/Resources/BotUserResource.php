<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\BotUser;

use MoonShine\ActionButtons\ActionButton;
use MoonShine\Fields\Text;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

/**
 * @extends ModelResource<BotUser>
 */
class BotUserResource extends ModelResource
{
    protected string $model = BotUser::class;

    protected string $title = 'Пользователи';

    public static array $activeActions = ['show'];

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                Text::make('Имя пользователя', 'text', function (BotUser $botUser) {
                    $fullName = (!empty($botUser->lastname)) ? $botUser->lastname . " " : "";
                    $fullName .= (!empty($botUser->firstname)) ? $botUser->firstname . " " : "";

                    return $fullName;
                })->readonly(),
                Text::make('Логин', 'username')->readonly(),
                Text::make('Дата изменения', 'updated_at')->readonly(),
                Text::make('Дата создания', 'created_at')->readonly(),
            ]),
        ];
    }

    public function getActiveActions(): array
    {
        return ['view'];
    }

    public function buttons(): array
    {

        return [
            ActionButton::make('Открыть чат', function(BotUser $data) {
                return "https://t.me/" . $data->username;
            })->customAttributes([
                'class' => 'btn btn-primary',
                'target' => '_blank',
            ])
        ];
    }

    /**
     * @param BotUser $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
