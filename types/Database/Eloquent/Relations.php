<?php

namespace Illuminate\Types\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

use function PHPStan\Testing\assertType;

function test(User $user, Post $post, Comment $comment, ChildUser $child): void
{
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<Illuminate\Types\Relations\Address, Illuminate\Types\Relations\User>', $user->address());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<Illuminate\Types\Relations\Address, Illuminate\Types\Relations\ChildUser>', $child->address());
    assertType('Illuminate\Types\Relations\Address|null', $user->address()->getResults());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Address>', $user->address()->get());

    assertType('Illuminate\Database\Eloquent\Relations\HasMany<Illuminate\Types\Relations\Post, Illuminate\Types\Relations\User>', $user->posts());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Post>', $user->posts()->getResults());
    assertType('Illuminate\Database\Eloquent\Relations\HasOne<Illuminate\Types\Relations\Post, Illuminate\Types\Relations\User>', $user->latestPost());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<Illuminate\Types\Relations\Role, Illuminate\Types\Relations\User>', $user->roles());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Role>', $user->roles()->getResults());

    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Types\Relations\Car, Illuminate\Types\Relations\Mechanic, Illuminate\Types\Relations\User>', $user->car());
    assertType('Illuminate\Types\Relations\Car|null', $user->car()->getResults());

    assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Types\Relations\Part, Illuminate\Types\Relations\Mechanic, Illuminate\Types\Relations\User>', $user->parts());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Part>', $user->parts()->getResults());
    assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Types\Relations\Part, Illuminate\Types\Relations\Mechanic, Illuminate\Types\Relations\User>', $user->firstPart());

    assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Illuminate\Types\Relations\User, Illuminate\Types\Relations\Post>', $post->user());
    assertType('Illuminate\Types\Relations\User|null', $post->user()->getResults());

    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<Illuminate\Types\Relations\Image, Illuminate\Types\Relations\Post>', $post->image());
    assertType('Illuminate\Types\Relations\Image|null', $post->image()->getResults());

    assertType('Illuminate\Database\Eloquent\Relations\MorphMany<Illuminate\Types\Relations\Comment, Illuminate\Types\Relations\Post>', $post->comments());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Comment>', $post->comments()->getResults());
    assertType('Illuminate\Database\Eloquent\Relations\MorphOne<Illuminate\Types\Relations\Comment, Illuminate\Types\Relations\Post>', $post->latestComment());

    assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, Illuminate\Types\Relations\Comment>', $comment->commentable());
    assertType('Illuminate\Database\Eloquent\Model|null', $comment->commentable()->getResults());

    assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<Illuminate\Types\Relations\Tag, Illuminate\Types\Relations\Post>', $post->tags());
    assertType('Illuminate\Database\Eloquent\Collection<int, Illuminate\Types\Relations\Tag>', $post->tags()->getResults());
}

class User extends Model
{
    /** @return HasOne<Address, $this> */
    public function address(): HasOne
    {
        $hasOne = $this->hasOne(Address::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<Illuminate\Types\Relations\Address, $this(Illuminate\Types\Relations\User)>', $hasOne);

        return $hasOne;
    }

    /** @return HasMany<Post, $this> */
    public function posts(): HasMany
    {
        $hasMany = $this->hasMany(Post::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasMany<Illuminate\Types\Relations\Post, $this(Illuminate\Types\Relations\User)>', $hasMany);

        return $hasMany;
    }

    /** @return HasOne<Post, $this> */
    public function latestPost(): HasOne
    {
        $post = $this->posts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOne<Illuminate\Types\Relations\Post, $this(Illuminate\Types\Relations\User)>', $post);

        return $post;
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        $belongsToMany = $this->belongsToMany(Role::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsToMany<Illuminate\Types\Relations\Role, $this(Illuminate\Types\Relations\User)>', $belongsToMany);

        return $belongsToMany;
    }

    /** @return HasOne<Mechanic, $this> */
    public function mechanic(): HasOne
    {
        return $this->hasOne(Mechanic::class);
    }

    /** @return HasOneThrough<Car, Mechanic, $this> */
    public function car(): HasOneThrough
    {
        $hasOneThrough = $this->hasOneThrough(Car::class, Mechanic::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Types\Relations\Car, Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>', $hasOneThrough);

        /** @phpstan-ignore argument.templateType (unable to resolve template type from string) */
        $through = $this->through('mechanic');
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<Illuminate\Database\Eloquent\Model, $this(Illuminate\Types\Relations\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(Illuminate\Types\Relations\User)>|Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Database\Eloquent\Model, Illuminate\Database\Eloquent\Model, $this(Illuminate\Types\Relations\User)>',
            /** @phpstan-ignore argument.templateType (unable to resolve template type from string) */
            $through->has('car'),
        );

        $through = $this->through($this->mechanic());
        assertType(
            'Illuminate\Database\Eloquent\PendingHasThroughRelationship<Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>',
            $through,
        );
        assertType(
            'Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Types\Relations\Car, Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>',
            $through->has(function ($mechanic) {
                assertType('Illuminate\Types\Relations\Mechanic', $mechanic);

                return $mechanic->car();
            }),
        );

        return $hasOneThrough;
    }

    /** @return HasManyThrough<Part, Mechanic, $this> */
    public function parts(): HasManyThrough
    {
        $hasManyThrough = $this->hasManyThrough(Part::class, Mechanic::class);
        assertType('Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Types\Relations\Part, Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>', $hasManyThrough);

        assertType(
            'Illuminate\Database\Eloquent\Relations\HasManyThrough<Illuminate\Types\Relations\Part, Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>',
            $this->through($this->mechanic())->has(fn ($mechanic) => $mechanic->parts()),
        );

        return $hasManyThrough;
    }

    /** @return HasOneThrough<Part, Mechanic, $this> */
    public function firstPart(): HasOneThrough
    {
        $part = $this->parts()->one();
        assertType('Illuminate\Database\Eloquent\Relations\HasOneThrough<Illuminate\Types\Relations\Part, Illuminate\Types\Relations\Mechanic, $this(Illuminate\Types\Relations\User)>', $part);

        return $part;
    }
}

class Post extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        $belongsTo = $this->belongsTo(User::class);
        assertType('Illuminate\Database\Eloquent\Relations\BelongsTo<Illuminate\Types\Relations\User, $this(Illuminate\Types\Relations\Post)>', $belongsTo);

        return $belongsTo;
    }

    /** @return MorphOne<Image, $this> */
    public function image(): MorphOne
    {
        $morphOne = $this->morphOne(Image::class, 'imageable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<Illuminate\Types\Relations\Image, $this(Illuminate\Types\Relations\Post)>', $morphOne);

        return $morphOne;
    }

    /** @return MorphMany<Comment, $this> */
    public function comments(): MorphMany
    {
        $morphMany = $this->morphMany(Comment::class, 'commentable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphMany<Illuminate\Types\Relations\Comment, $this(Illuminate\Types\Relations\Post)>', $morphMany);

        return $morphMany;
    }

    /** @return MorphOne<Comment, $this> */
    public function latestComment(): MorphOne
    {
        $comment = $this->comments()->one();
        assertType('Illuminate\Database\Eloquent\Relations\MorphOne<Illuminate\Types\Relations\Comment, $this(Illuminate\Types\Relations\Post)>', $comment);

        return $comment;
    }

    /** @return MorphToMany<Tag, $this> */
    public function tags(): MorphToMany
    {
        $morphToMany = $this->morphedByMany(Tag::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<Illuminate\Types\Relations\Tag, $this(Illuminate\Types\Relations\Post)>', $morphToMany);

        return $morphToMany;
    }
}

class Comment extends Model
{
    /** @return MorphTo<\Illuminate\Database\Eloquent\Model, $this> */
    public function commentable(): MorphTo
    {
        $morphTo = $this->morphTo();
        assertType('Illuminate\Database\Eloquent\Relations\MorphTo<Illuminate\Database\Eloquent\Model, $this(Illuminate\Types\Relations\Comment)>', $morphTo);

        return $morphTo;
    }
}

class Tag extends Model
{
    /** @return MorphToMany<Post, $this> */
    public function posts(): MorphToMany
    {
        $morphToMany = $this->morphToMany(Post::class, 'taggable');
        assertType('Illuminate\Database\Eloquent\Relations\MorphToMany<Illuminate\Types\Relations\Post, $this(Illuminate\Types\Relations\Tag)>', $morphToMany);

        return $morphToMany;
    }
}

class Mechanic extends Model
{
    /** @return HasOne<Car, $this> */
    public function car(): HasOne
    {
        return $this->hasOne(Car::class);
    }

    /** @return HasMany<Part, $this> */
    public function parts(): HasMany
    {
        return $this->hasMany(Part::class);
    }
}

class ChildUser extends User
{
}
class Address extends Model
{
}
class Role extends Model
{
}
class Car extends Model
{
}
class Part extends Model
{
}
class Image extends Model
{
}
