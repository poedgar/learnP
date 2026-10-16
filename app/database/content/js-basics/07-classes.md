## Blueprints for objects

A **class** describes a kind of object: the data each one holds and the methods that work on it. `new` creates an
**instance**, running the `constructor` to set it up:

```javascript
class Book {
    constructor(title, pence) {
        this.title = title;
        this.pence = pence;
    }

    label() {
        return `${this.title}: £${(this.pence / 100).toFixed(2)}`;
    }
}

const dune = new Book('Dune', 999);
const emma = new Book('Emma', 799);

console.log(dune.label(), emma.label());
console.log(dune instanceof Book);
```

Inside a method, `this` is the instance the method was called on. Methods are shared by all instances; the data in
`this` belongs to each one.

## Fields

Properties can be declared at the top of the class as **fields**, with starting values. They're set up before the
constructor runs:

```javascript
class Basket {
    items = [];
    discount = 0;

    add(pence) {
        this.items.push(pence);
        return this;
    }

    total() {
        const sum = this.items.reduce((a, b) => a + b, 0);
        return sum - Math.round((sum * this.discount) / 100);
    }
}

const basket = new Basket().add(500).add(250);
basket.discount = 10;
console.log(basket.total());
```

Returning `this` from a method lets calls be **chained**.

## Private fields

A field whose name starts with `#` is **private**: only code inside the class can read or change it. That lets the
class protect its own rules:

```javascript
class Account {
    #balance = 0;

    deposit(pence) {
        if (pence <= 0) {
            throw new Error('Deposits must be positive');
        }
        this.#balance += pence;
    }

    get balance() {
        return this.#balance;
    }
}

const account = new Account();
account.deposit(500);
console.log(account.balance);

try {
    account.balance = 1000000;
} catch (error) {
    console.log(error.name, account.balance);
}
```

`balance` has a getter but no setter, so in strict code, which the sandbox and all modern code use, assigning to it
is a `TypeError`.

## Getters and setters

A **getter** (`get name()`) is a method that's read like a property; a **setter** (`set name(value)`) runs when the
property is assigned, so it can check the value:

```javascript
class Temperature {
    #celsius = 0;

    get celsius() {
        return this.#celsius;
    }

    set celsius(value) {
        if (value < -273.15) {
            throw new RangeError('Below absolute zero');
        }
        this.#celsius = value;
    }

    get fahrenheit() {
        return this.#celsius * 1.8 + 32;
    }
}

const t = new Temperature();
t.celsius = 25;
console.log(t.celsius, t.fahrenheit);
```

## Static members

**Static** methods and fields belong to the class itself, not to instances. They suit helpers and alternative ways
to create instances:

```javascript
class Money {
    static currency = 'GBP';

    constructor(pence) {
        this.pence = pence;
    }

    static fromPounds(pounds) {
        return new Money(Math.round(pounds * 100));
    }
}

console.log(Money.fromPounds(12.5).pence, Money.currency);
```

## Inheritance

`extends` makes a class that builds on another. The child gets the parent's methods, can add its own, and can
override them. `super(...)` calls the parent's constructor, and `super.method()` the parent's version of a method:

```javascript
class Animal {
    constructor(name) {
        this.name = name;
    }

    describe() {
        return `${this.name} makes a sound`;
    }
}

class Dog extends Animal {
    constructor(name, breed) {
        super(name);
        this.breed = breed;
    }

    describe() {
        return `${super.describe()}: woof (a ${this.breed})`;
    }
}

const rex = new Dog('Rex', 'collie');
console.log(rex.describe());
console.log(rex instanceof Dog, rex instanceof Animal);
```

In a child class, `super()` must run before `this` is used.

## Losing `this`

`this` is set by how a method is **called**. Taking a method off its object and calling it on its own, as happens
when passing it as a callback, leaves `this` as `undefined`:

```javascript
// Error: the method is called without its object, so this is undefined.
class Clicker {
    count = 0;

    click() {
        this.count++;
    }
}

const clicker = new Clicker();
const handler = clicker.click;
handler();
```

Two fixes: wrap it in an arrow function, `() => clicker.click()`, or define the method as an arrow-function field,
which keeps the `this` of the instance it was created in:

```javascript
class Clicker {
    count = 0;

    click = () => {
        this.count++;
    };
}

const clicker = new Clicker();
const handler = clicker.click;
handler();
handler();
console.log(clicker.count);
```

## `toString` and how objects print

Defining `toString()` controls how an object turns into text, such as in a template literal:

```javascript
class Point {
    constructor(x, y) {
        this.x = x;
        this.y = y;
    }

    toString() {
        return `(${this.x}, ${this.y})`;
    }
}

const p = new Point(3, 4);
console.log(`The point is ${p}`);
console.log(p);
```

## Putting it together

A small class hierarchy for shapes, each answering for its own area:

```javascript
class Shape {
    area() {
        return 0;
    }

    describe() {
        return `${this.constructor.name} with area ${this.area().toFixed(2)}`;
    }
}

class Circle extends Shape {
    constructor(radius) {
        super();
        this.radius = radius;
    }

    area() {
        return Math.PI * this.radius ** 2;
    }
}

class Square extends Shape {
    constructor(side) {
        super();
        this.side = side;
    }

    area() {
        return this.side ** 2;
    }
}

for (const shape of [new Circle(1), new Square(3)]) {
    console.log(shape.describe());
}
```

Next, try the quiz to check your understanding, then solve the challenges.
