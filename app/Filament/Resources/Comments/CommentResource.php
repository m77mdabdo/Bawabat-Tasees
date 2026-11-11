<?php

namespace App\Filament\Resources\Comments;

use App\Filament\Concerns\GuardedByPermissions;
use App\Filament\Resources\Comments\Pages\ListComments;
use App\Models\Article;
use App\Models\Comment;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/** Article comments: nothing is shown on the site until a moderator approves it. */
class CommentResource extends Resource
{
    use GuardedByPermissions;

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $model = Comment::class;

    protected static string $viewPermission = 'comments.moderate';

    protected static string $managePermission = 'comments.moderate';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    protected static ?int $navigationSort = 6;

    public static function getNavigationGroup(): ?string
    {
        return __('Website content');
    }

    public static function getModelLabel(): string
    {
        return __('Comment');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Comments');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Comment::query()->pending()->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return ['pending' => __('Pending'), 'approved' => __('Approved'), 'rejected' => __('Rejected')];
    }

    private static function setStatus(string $status): \Closure
    {
        return fn (Comment $record) => $record->update(['status' => $status]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('article'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label(__('Name'))->weight('semibold')->searchable()->description(fn (Comment $c) => $c->email),
                TextColumn::make('body')->label(__('Comment'))->wrap()->limit(160)->extraAttributes(['dir' => 'auto']),
                TextColumn::make('article.title')->label(__('Article'))->limit(40)->toggleable(),
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn (string $state) => self::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'approved' => 'success', 'rejected' => 'danger', default => 'warning'
                    }),
                TextColumn::make('created_at')->label(__('Received'))->since()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(__('Status'))->options(self::statusOptions())->default('pending'),
                SelectFilter::make('article_id')->label(__('Article'))
                    ->options(fn () => Article::query()->orderByDesc('published_at')->get()->mapWithKeys(fn (Article $a) => [$a->id => $a->title])->all()),
            ])
            ->recordActions([
                Action::make('approve')->label(__('Approve'))->icon('heroicon-o-check')->color('success')
                    ->visible(fn (Comment $c) => $c->status !== 'approved')->action(self::setStatus('approved')),
                Action::make('reject')->label(__('Reject'))->icon('heroicon-o-x-mark')->color('danger')
                    ->visible(fn (Comment $c) => $c->status !== 'rejected')->action(self::setStatus('rejected')),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('approveAll')->label(__('Approve'))->icon('heroicon-o-check')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'approved']))->deselectRecordsAfterCompletion(),
                    BulkAction::make('rejectAll')->label(__('Reject'))->icon('heroicon-o-x-mark')
                        ->action(fn (Collection $records) => $records->each->update(['status' => 'rejected']))->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListComments::route('/')];
    }
}
