<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Article;


use MoonShine\Fields\Textarea;
use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Field;
use MoonShine\Components\MoonShineComponent;

use MoonShine\Fields\Text;
use MoonShine\Fields\Number;

/**
 * @extends ModelResource<Article>
 */
class ArticleResource extends ModelResource
{
    protected string $model = Article::class;

    protected string $title = 'Статьи';

    protected int $itemsPerPage = 10;
    protected string $sortColumn = 'article';

    /**
     * @return list<MoonShineComponent|Field>
     */
    public function fields(): array
    {
        return [
            Block::make([
                ID::make()->sortable(),
                Number::make('Статья', 'article')->sortable()->required(),
                Number::make('Часть', 'chapter')->sortable()->required(),
                Number::make('Раздел', 'section')->required(),
                Textarea::make('Текст', 'text')->required()
            ]),
        ];
    }

    /**
     * @param Article $item
     *
     * @return array<string, string[]|string>
     * @see https://laravel.com/docs/validation#available-validation-rules
     */
    public function rules(Model $item): array
    {
        return [];
    }
}
