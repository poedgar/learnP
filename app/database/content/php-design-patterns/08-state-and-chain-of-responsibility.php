<?php

// Lesson 8 of the Design patterns in PHP course. The theory is in the .md file with the same name.
// Each challenge's checks run after the learner's code; a check returns true or a message.

return [
    'slug' => 'state-and-chain-of-responsibility',
    'title' => 'State and Chain of Responsibility',
    'summary' => 'Give each situation its own state class instead of status checks everywhere, and pass requests along a chain of handlers, including middleware.',
    'starter_code' => <<<'PHP'
        <?php

        // Change this code, then press Run (Ctrl+Enter).
        enum Status: string
        {
            case New = 'new';
            case Paid = 'paid';
            case Shipped = 'shipped';

            public function next(): ?self
            {
                return match ($this) {
                    self::New => self::Paid,
                    self::Paid => self::Shipped,
                    self::Shipped => null,
                };
            }
        }

        $status = Status::New;
        while ($status !== null) {
            echo $status->value . "\n";
            $status = $status->next();
        }
        PHP,

    'quiz' => [
        [
            'question' => 'What does the State pattern replace?',
            'options' => [
                'Interfaces',
                'Status checks scattered through every method, with one class per state holding that state\'s rules',
                'Constructors',
                'Exceptions',
            ],
            'correct_option' => 1,
            'explanation' => 'All the rules for "paid" live in one class, and a new state is a new class.',
        ],
        [
            'question' => 'In the State pattern, how does an order move from "new" to "paid"?',
            'options' => [
                'The caller changes a status string',
                'The current state object replaces the order\'s state with the next state object',
                'The order creates every state up front',
                'PHP does it automatically',
            ],
            'correct_option' => 1,
            'explanation' => 'Each state knows which states can follow it, and sets `$order->state` accordingly.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                interface Light { public function next(): Light; public function name(): string; }

                class Red implements Light { public function next(): Light { return new Green(); } public function name(): string { return 'red'; } }
                class Green implements Light { public function next(): Light { return new Amber(); } public function name(): string { return 'green'; } }
                class Amber implements Light { public function next(): Light { return new Red(); } public function name(): string { return 'amber'; } }

                $light = new Red();
                for ($i = 0; $i < 4; $i++) {
                    $light = $light->next();
                }
                echo $light->name();
                PHP,
            'options' => ['`red`', '`green`', '`amber`', 'An error'],
            'correct_option' => 1,
            'explanation' => 'Red → green → amber → red → green: four steps around a cycle of three.',
        ],
        [
            'question' => 'When is an enum enough instead of a class per state?',
            'options' => [
                'Never',
                'When the states have no real behaviour of their own, just which states may follow',
                'Only when there are exactly two states',
                'When the states need to store different data',
            ],
            'correct_option' => 1,
            'explanation' => 'A `canMoveTo()` method on an enum covers simple state machines with much less code.',
        ],
        [
            'question' => 'In a chain of responsibility, what does a handler do with a request it can\'t deal with?',
            'options' => [
                'Throws an exception',
                'Passes it to the next handler in the chain',
                'Ignores it silently',
                'Sends it back to the caller',
            ],
            'correct_option' => 1,
            'explanation' => 'Each handler either handles the request or hands it on, until one handles it or the chain ends.',
        ],
        [
            'question' => 'What does the code that sends a request need to know about the chain?',
            'options' => [
                'Every handler in it',
                'Only the first handler',
                'Which handler will answer',
                'The order of the handlers',
            ],
            'correct_option' => 1,
            'explanation' => 'The chain can be rearranged or extended without changing the caller.',
        ],
        [
            'question' => 'What does this code print?',
            'code' => <<<'PHP'
                $layers = [
                    fn ($x, $next) => '(' . $next($x) . ')',
                    fn ($x, $next) => '[' . $next($x) . ']',
                ];

                $core = fn ($x) => $x;
                foreach (array_reverse($layers) as $layer) {
                    $core = fn ($x) => $layer($x, $core);
                }

                echo $core('dune');
                PHP,
            'options' => ['`([dune])`', '`[(dune)]`', '`(dune)[dune]`', '`dune`'],
            'correct_option' => 0,
            'explanation' => 'The first layer is outermost: it wraps whatever the second layer, wrapping the core, returns.',
        ],
        [
            'question' => 'In middleware, what happens when a layer doesn\'t call `$next`?',
            'options' => [
                'The next layer runs anyway',
                'The chain stops there, and that layer\'s result is the answer',
                'An error is thrown',
                'The request is retried',
            ],
            'correct_option' => 1,
            'explanation' => 'That\'s how an authentication layer can refuse a request before it reaches the page.',
        ],
    ],

    'challenges' => [
        [
            'title' => 'A document\'s life',
            'prompt' => "A `Document` starts as a draft, can be submitted for review, then published. Write the states `Draft`, `InReview` and `Published`, implementing `DocState` with `submit(Document)`, `publish(Document)` and `name()`. Allowed moves return `'Submitted'` / `'Published'` and change the state; anything else returns `\"Can't do that while NAME\"` (with the state's name: `draft`, `in review` or `published`).",
            'starter_code' => <<<'PHP'
                <?php

                interface DocState
                {
                    public function submit(Document $doc): string;
                    public function publish(Document $doc): string;
                    public function name(): string;
                }

                class Document
                {
                    public DocState $state;

                    public function __construct()
                    {
                        $this->state = new Draft();
                    }

                    public function submit(): string { return $this->state->submit($this); }
                    public function publish(): string { return $this->state->publish($this); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                interface DocState
                {
                    public function submit(Document $doc): string;
                    public function publish(Document $doc): string;
                    public function name(): string;
                }

                class Document
                {
                    public DocState $state;

                    public function __construct()
                    {
                        $this->state = new Draft();
                    }

                    public function submit(): string { return $this->state->submit($this); }
                    public function publish(): string { return $this->state->publish($this); }
                }

                class Draft implements DocState
                {
                    public function submit(Document $doc): string { $doc->state = new InReview(); return 'Submitted'; }
                    public function publish(Document $doc): string { return "Can't do that while {$this->name()}"; }
                    public function name(): string { return 'draft'; }
                }

                class InReview implements DocState
                {
                    public function submit(Document $doc): string { return "Can't do that while {$this->name()}"; }
                    public function publish(Document $doc): string { $doc->state = new Published(); return 'Published'; }
                    public function name(): string { return 'in review'; }
                }

                class Published implements DocState
                {
                    public function submit(Document $doc): string { return "Can't do that while {$this->name()}"; }
                    public function publish(Document $doc): string { return "Can't do that while {$this->name()}"; }
                    public function name(): string { return 'published'; }
                }
                PHP,
            'hint' => 'Three classes. `Draft::submit()` sets `$doc->state = new InReview()`; `InReview::publish()` sets `new Published()`; everything else refuses.',
            'checks' => [
                ['label' => 'The happy path', 'code' => "\$doc = new Document();\nreturn expect_same([\$doc->submit(), \$doc->publish(), \$doc->state->name()], ['Submitted', 'Published', 'published']);"],
                ['label' => 'Refusals name the state', 'code' => "\$doc = new Document();\n\$a = \$doc->publish();\n\$doc->submit();\n\$b = \$doc->submit();\n\$doc->publish();\n\$c = \$doc->publish();\nreturn expect_same([\$a, \$b, \$c], [\"Can't do that while draft\", \"Can't do that while in review\", \"Can't do that while published\"]);"],
            ],
        ],
        [
            'title' => 'Allowed moves',
            'prompt' => 'Give the `Ticket` enum a method `canMoveTo(Ticket $next): bool`. Allowed moves: Open → InProgress or Closed; InProgress → Open or Resolved; Resolved → Closed or Open; Closed → nothing.',
            'starter_code' => <<<'PHP'
                <?php

                enum Ticket
                {
                    case Open;
                    case InProgress;
                    case Resolved;
                    case Closed;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                enum Ticket
                {
                    case Open;
                    case InProgress;
                    case Resolved;
                    case Closed;

                    public function canMoveTo(Ticket $next): bool
                    {
                        $allowed = match ($this) {
                            self::Open => [self::InProgress, self::Closed],
                            self::InProgress => [self::Open, self::Resolved],
                            self::Resolved => [self::Closed, self::Open],
                            self::Closed => [],
                        };

                        return in_array($next, $allowed, true);
                    }
                }
                PHP,
            'hint' => 'A `match ($this)` giving the list of allowed next cases, and `in_array($next, $allowed, true)`.',
            'checks' => [
                ['label' => 'Allowed moves', 'code' => 'return expect_same([Ticket::Open->canMoveTo(Ticket::InProgress), Ticket::InProgress->canMoveTo(Ticket::Resolved), Ticket::Resolved->canMoveTo(Ticket::Open)], [true, true, true]);'],
                ['label' => 'Refused moves', 'code' => 'return expect_same([Ticket::Open->canMoveTo(Ticket::Resolved), Ticket::Closed->canMoveTo(Ticket::Open), Ticket::InProgress->canMoveTo(Ticket::Closed)], [false, false, false]);'],
            ],
        ],
        [
            'title' => 'Who approves the refund?',
            'prompt' => "Write the handlers `Bookseller` (up to and including £20), `Manager` (up to £200) and `Owner` (any amount), extending `Approver`. Each implements `canApprove(float)` and `role()` (`'bookseller'`, `'manager'`, `'owner'`). `handle()` (given) returns the role of the first handler in the chain that can approve.",
            'starter_code' => <<<'PHP'
                <?php

                abstract class Approver
                {
                    private ?Approver $next = null;

                    public function then(Approver $next): Approver
                    {
                        $this->next = $next;
                        return $next;
                    }

                    public function handle(float $amount): ?string
                    {
                        return $this->canApprove($amount) ? $this->role() : $this->next?->handle($amount);
                    }

                    abstract protected function canApprove(float $amount): bool;
                    abstract protected function role(): string;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class Approver
                {
                    private ?Approver $next = null;

                    public function then(Approver $next): Approver
                    {
                        $this->next = $next;
                        return $next;
                    }

                    public function handle(float $amount): ?string
                    {
                        return $this->canApprove($amount) ? $this->role() : $this->next?->handle($amount);
                    }

                    abstract protected function canApprove(float $amount): bool;
                    abstract protected function role(): string;
                }

                class Bookseller extends Approver
                {
                    protected function canApprove(float $amount): bool { return $amount <= 20; }
                    protected function role(): string { return 'bookseller'; }
                }

                class Manager extends Approver
                {
                    protected function canApprove(float $amount): bool { return $amount <= 200; }
                    protected function role(): string { return 'manager'; }
                }

                class Owner extends Approver
                {
                    protected function canApprove(float $amount): bool { return true; }
                    protected function role(): string { return 'owner'; }
                }
                PHP,
            'hint' => 'Three small classes, each with its own limit and role. The chain logic is already in `Approver`.',
            'checks' => [
                ['label' => 'Each amount reaches the right person', 'code' => "\$chain = new Bookseller();\n\$chain->then(new Manager())->then(new Owner());\nreturn expect_same(array_map(fn (\$a) => \$chain->handle(\$a), [5.0, 20.0, 20.01, 200.0, 9999.0]), ['bookseller', 'bookseller', 'manager', 'manager', 'owner']);"],
                ['label' => 'A short chain can give up', 'code' => "\$chain = new Bookseller();\n\$chain->then(new Manager());\nreturn expect_same(\$chain->handle(500.0), null);"],
            ],
        ],
        [
            'title' => 'Validation as a chain',
            'prompt' => "Write `validate(string \$password, Closure ...\$rules): ?string` that runs each rule in order and returns the first error message (a rule returns a message, or `null` when the password passes it), or `null` if all pass. Then set `\$rules` to three rules: at least 8 characters (`'Too short'`), contains a digit (`'Needs a digit'`), and isn't `'password1'` (`'Too common'`).",
            'starter_code' => <<<'PHP'
                <?php

                function validate(string $password, Closure ...$rules): ?string
                {
                    return null;
                }

                $rules = [];
                PHP,
            'solution' => <<<'PHP'
                <?php

                function validate(string $password, Closure ...$rules): ?string
                {
                    foreach ($rules as $rule) {
                        $error = $rule($password);
                        if ($error !== null) {
                            return $error;
                        }
                    }

                    return null;
                }

                $rules = [
                    fn (string $p) => strlen($p) < 8 ? 'Too short' : null,
                    fn (string $p) => preg_match('/\d/', $p) ? null : 'Needs a digit',
                    fn (string $p) => $p === 'password1' ? 'Too common' : null,
                ];
                PHP,
            'hint' => 'Loop over the rules and return the first non-null result. Each rule is an arrow function returning a message or `null`.',
            'checks' => [
                ['label' => 'Each rule\'s message, in order', 'code' => "\$rules = \$GLOBALS['rules'];\nreturn expect_same([validate('abc', ...\$rules), validate('abcdefgh', ...\$rules), validate('password1', ...\$rules)], ['Too short', 'Needs a digit', 'Too common']);"],
                ['label' => 'A good password passes', 'code' => "return expect_same(validate('correct-horse-7', ...\$GLOBALS['rules']), null);"],
                ['label' => 'The first failing rule wins', 'code' => "return expect_same(validate('abc', ...array_reverse(\$GLOBALS['rules'])), 'Needs a digit');"],
            ],
        ],
        [
            'title' => 'Build a middleware pipeline',
            'prompt' => 'Write `pipeline(array $middleware, Closure $core): Closure`. Each middleware is a closure `fn ($request, Closure $next)`. The returned closure runs the middleware in order, first one outermost, ending at `$core`.',
            'starter_code' => <<<'PHP'
                <?php

                function pipeline(array $middleware, Closure $core): Closure
                {
                    return $core;
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                function pipeline(array $middleware, Closure $core): Closure
                {
                    $handler = $core;
                    foreach (array_reverse($middleware) as $layer) {
                        $next = $handler;
                        $handler = fn ($request) => $layer($request, $next);
                    }

                    return $handler;
                }
                PHP,
            'hint' => 'Wrap from the inside out: go through the middleware in reverse, each time making a new closure that calls the layer with the previous handler as `$next`.',
            'checks' => [
                ['label' => 'Layers wrap in order', 'code' => "\$run = pipeline([fn (\$r, \$next) => 'A(' . \$next(\$r) . ')', fn (\$r, \$next) => 'B(' . \$next(\$r) . ')'], fn (\$r) => strtoupper(\$r));\nreturn expect_same(\$run('dune'), 'A(B(DUNE))');"],
                ['label' => 'A layer can stop the chain', 'code' => "\$run = pipeline([fn (\$r, \$next) => \$r === 'secret' ? 'Denied' : \$next(\$r)], fn (\$r) => \"Page \$r\");\nreturn expect_same([\$run('home'), \$run('secret')], ['Page home', 'Denied']);"],
                ['label' => 'No middleware runs the core', 'code' => "return expect_same(pipeline([], fn (\$r) => \"core:\$r\")('x'), 'core:x');"],
            ],
        ],
        [
            'title' => 'Loans that can be renewed twice',
            'prompt' => "A `Loan` is on the shelf, then on loan. While on loan it can be renewed at most twice. Write the states `OnShelf` and `OnLoan` (extending `LoanState`, whose defaults refuse). `OnLoan` keeps a count of renewals. Results: `'Borrowed'`, `'Renewed'`, `'Returned'`, `'No more renewals'`, or the default refusal.",
            'starter_code' => <<<'PHP'
                <?php

                abstract class LoanState
                {
                    public function borrow(Loan $loan): string { return 'Not possible'; }
                    public function renew(Loan $loan): string { return 'Not possible'; }
                    public function giveBack(Loan $loan): string { return 'Not possible'; }
                }

                class Loan
                {
                    public LoanState $state;

                    public function __construct() { $this->state = new OnShelf(); }

                    public function borrow(): string { return $this->state->borrow($this); }
                    public function renew(): string { return $this->state->renew($this); }
                    public function giveBack(): string { return $this->state->giveBack($this); }
                }
                PHP,
            'solution' => <<<'PHP'
                <?php

                abstract class LoanState
                {
                    public function borrow(Loan $loan): string { return 'Not possible'; }
                    public function renew(Loan $loan): string { return 'Not possible'; }
                    public function giveBack(Loan $loan): string { return 'Not possible'; }
                }

                class Loan
                {
                    public LoanState $state;

                    public function __construct() { $this->state = new OnShelf(); }

                    public function borrow(): string { return $this->state->borrow($this); }
                    public function renew(): string { return $this->state->renew($this); }
                    public function giveBack(): string { return $this->state->giveBack($this); }
                }

                class OnShelf extends LoanState
                {
                    public function borrow(Loan $loan): string
                    {
                        $loan->state = new OnLoan();
                        return 'Borrowed';
                    }
                }

                class OnLoan extends LoanState
                {
                    private int $renewals = 0;

                    public function renew(Loan $loan): string
                    {
                        if ($this->renewals >= 2) {
                            return 'No more renewals';
                        }
                        $this->renewals++;
                        return 'Renewed';
                    }

                    public function giveBack(Loan $loan): string
                    {
                        $loan->state = new OnShelf();
                        return 'Returned';
                    }
                }
                PHP,
            'hint' => '`OnShelf` only overrides `borrow()`. `OnLoan` has a private renewals counter, and overrides `renew()` and `giveBack()`. A fresh `OnLoan` on each borrow resets the count.',
            'checks' => [
                ['label' => 'Borrow, renew twice, refuse the third', 'code' => "\$loan = new Loan();\nreturn expect_same([\$loan->borrow(), \$loan->renew(), \$loan->renew(), \$loan->renew(), \$loan->giveBack()], ['Borrowed', 'Renewed', 'Renewed', 'No more renewals', 'Returned']);"],
                ['label' => 'A new loan starts the count again', 'code' => "\$loan = new Loan();\n\$loan->borrow(); \$loan->renew(); \$loan->renew(); \$loan->giveBack();\nreturn expect_same([\$loan->renew(), \$loan->borrow(), \$loan->renew()], ['Not possible', 'Borrowed', 'Renewed']);"],
            ],
        ],
    ],
];
