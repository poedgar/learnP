## The database's history, in code

A team's databases (your laptop, a colleague's, the test server, production) all need the same tables
and columns. Instead of changing each by hand, Laravel describes every change in a **migration**: a
PHP file in `database/migrations` with an `up()` method that makes the change and a `down()` method that
undoes it.

```laravel
// php artisan make:migration create_books_table

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->decimal('price', 8, 2)->default(0);
            $table->boolean('in_stock')->default(true);
            $table->foreignId('author_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
```

`php artisan migrate` runs every migration that hasn't run yet; `migrate:rollback` undoes the last batch;
`migrate:fresh` drops everything and starts again (never on production).

## Columns

Each `$table->...` call adds a column. The common ones:

| Method | Column |
|---|---|
| `id()` | an auto-incrementing big integer primary key named `id` |
| `string('title')` | `varchar(255)`, for short text |
| `text('summary')` | long text |
| `integer('pages')`, `decimal('price', 8, 2)` | numbers |
| `boolean('in_stock')` | true or false |
| `date('published_on')`, `timestamp('paid_at')` | dates and times |
| `timestamps()` | `created_at` and `updated_at`, filled in by Eloquent |

Modifiers change a column: `->nullable()` allows empty values (columns are required by default),
`->default(...)` sets a default, `->unique()` forbids duplicates.

A migration describes the change; Laravel turns it into SQL for your database. For one column:

```php
$column = ['name' => 'price', 'type' => 'decimal(8, 2)', 'nullable' => false, 'default' => 0];

$sql = $column['name'] . ' ' . $column['type'] . ($column['nullable'] ? ' null' : ' not null');
if ($column['default'] !== null) {
    $sql .= ' default ' . $column['default'];
}

echo $sql;
```

## Foreign keys

`foreignId('author_id')->constrained()` adds an `author_id` column and a **foreign key** that makes
the database refuse an `author_id` with no matching author. Laravel guesses the table from the column
name: `author_id` points at `authors`. `->cascadeOnDelete()` deletes an author's books along with the
author; `->nullOnDelete()` sets their `author_id` to null instead.

## Order and batches

Migration files start with a timestamp (`2026_10_21_093000_create_books_table.php`), and they run in
that order, so `authors` is created before `books` refers to it. Laravel records each migration it ran
in a `migrations` table, along with a **batch** number: everything run by one `php artisan migrate` shares
a batch, and `migrate:rollback` undoes the latest batch, newest migration first.

## Never edit a migration that has run

Once a migration has run anywhere else (a colleague's machine, production), changing the file does
nothing there: Laravel sees it already ran. To change a table, write a **new** migration:

```laravel
// php artisan make:migration add_isbn_to_books_table

Schema::table('books', function (Blueprint $table) {
    $table->string('isbn', 13)->nullable()->after('title');
});

// down():
Schema::table('books', function (Blueprint $table) {
    $table->dropColumn('isbn');
});
```

A good `down()` is the exact reverse of `up()`, step by step in reverse order, so rolling back and
migrating again always gives the same database.
