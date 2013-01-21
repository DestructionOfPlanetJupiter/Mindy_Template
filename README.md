# Flow - Fast PHP Templating Engine

## Introduction

Flow began life as a major fork of the original Twig templating engine by Armin
Ronacher, which he made for [Chyrp], a blogging engine. Flow features template
inheritance, includes, macros, custom helpers, autoescaping, whitespace control
and many little features that makes writing templates enjoyable. Flow compiles
each template into its own PHP class; used with APC, this makes Flow a very fast
and efficient templating engine.

## Installation

The easiest way to install is by using [Composer]; the minimum composer.json
configuration is:

```
{
    "require": {
        "flow/flow": "@dev"
    }
}
```

Flow requires PHP 5.3 or newer.

## Usage

Using Flow in your code is straight forward:

```php
<?php
require 'path/to/src/Flow/Loader.php';
use Flow\Loader;
Loader::autoload();
$flow = new Loader(array(
    'source' => 'path/to/templates',
    'target' => 'path/to/cache',
));
$template = $flow->load('home.html');
$template->display(array(
    'data_1' => 'My first data',
    'data_2' => 'My second data',
));
```

The `Loader` constructor accepts an array of options. They are:

- `source`: Directory to template source files.
- `target`: Directory to compiled PHP files.
- `reload`: Set to true to always reload templates; defaults to false.
- `prefix`: Compiled class name prefix; defaults to `__Template_`.
- `helpers` : Array of custom helpers.

## Basic Concepts

Flow uses `{%` and `%}` to delimit block tags. Block tags are used mainly
for block declaration in template inheritance and control structures. Examples
of block tags are `block`, `for`, and `if`. Some block tags may have a body
segment. They're usually enclosed by a corresponding `end<tag>` tag. Flow uses
`{{` and `}}` to delimit output tags, and `{#` and `#}` to delimit comments.
Keywords and identifiers are *case-sensitive*.

## Comments

Use `{#` and `#}` to delimit comments:

    {# This is a comment. It will be ignored. #}

Comments may span multiple lines but cannot be nested; they will be completely
removed from the resulting output.

## Expression Output

To output a literal, variable or any kind of expression, use the opening `{{`
and the closing `}}` tags:

    Hello, {{ username }}

    {{ "Welcome back, " .. username }}

    {{ "Two plus two equals " .. 2 + 2 }}

## Literals

There are several types of literals: numbers, strings, booleans, arrays, and
nulls.

### Numbers

Numbers can be integers of floats:

    {{ 42 }} and {{ 3.14 }}

Large numbers can be separated by underscores to make it more readable:

    Price: {{ 12_000 | number_format }} USD

The exact placing of _ is insignificant, although the first character must be a
digit; any _ character inside numbers will be removed. Numbers are translated
into PHP numbers and thus are limited by how PHP handles numbers with regards to
upper/lower limits and precision. Complex numeric and monetary operations should
be done in PHP using the GMP extension or the bcmath extension instead.

### Strings

Strings can either be double quoted or single quoted; both recognize escape
sequence characters. There are no support for variable extrapolation. Use string
concatenation instead:

    {{ "This is a string " .. 'This is also a string' }}

### Booleans

    {{ true }} or {{ false }}

When printed, `true` will be converted to `1` while `false` will be converted to
an empty string.

### Arrays

    {{ ["this", "is", "an", "array"][0] }}

Arrays are also hash tables just like in PHP:

    {{ ["foo" => "bar", 'oof' => 'rab']['foo'] }}

Printing arrays will cause a PHP notice to be thrown. Use the `join` helper:

    {{ [1,2,3] | join(', ') }}

### Nulls

    {{ null }}

When printed, `null` will be converted to an empty string.

## Operators

In addtition to short-circuiting, boolean operators `or` and `and` returns one
of their operands. This means you can, for example, do the following:

    Status: {{ user.status or "default value" }}

Furthermore, comparison operators can take multiple operands:

    {% if 1 <= x <= 10 %}
    <p>x is between 1 and 10 inclusive.</p>
    {% endif %}

Which is equivalent to:

    {% if 1 <= x and x <= 10 %}
    <p>x is between 1 and 10 inclusive.</p>
    {% endif %}

The `in` operator works with arrays:

    {% if 1 in [1,2,3] %}
    1 is definitely in 1,2,3
    {% endif %}

    {% if 1 not in [4,5,6] %}
    1 is definitely not in 4,5,6
    {% endif %}

Use `..` (a double dot) to concatenate between two or more scalars as strings:

    {{ "Hello," .. " World!" }}

String concatenation has a lower precedence than arithmatic
operators:

    {{ "1 + 1 = " .. 1 + 1 .. " and everything is OK again!" }}

Will yield

    1 + 1 = 2 and everything is OK again!

String output and concatenation coerce scalar values into strings.

### Operator precedence

Below is a list of all operators in Flow sorted according to their precedence in
descending order:

- Attribute access: `.` and `[]` for objects and arrays
- Filter chaining: `|`
- Arithmatic: unary `-` and `+`, `%`, `/`, `*`, `-`, `+`
- Concatenation: `..`
- Comparison: `!==`, `===`, `==`, `!=`, `<>`, `<`, `>`, `>=`, `<=`
- Conditional: `in`, `not`, `and`, `or`, `xor`
- Ternary: `? :`

## Attribute access

### Objects

You can access an object's member variables or methods using the `.` operator:

    {{ user.name }}

    {{ user.get_full_name() }}

When calling an object's method, the parentheses are optional when there are no
arguments passed. The full semantics of object attribute access are as follows:

1. Check if the attribute is a callable method. If it is, call and return the
   invoked method.
2. Check if the attribute is a member variable. If it is, return the value.
3. If it's neither a method nor a member variable, return null.

If your object implements `__call` or `__get` then it will invoke those methods
for rules #1 and #2 respectively regardless of whether the method or member
variable actually exists.

### Arrays

You can return an element of an array using either the `.` operator or the `[`
and `]` operator:

    {{ user.name }} is the same as {{ user['name'] }}

    {{ users[0] }}

The `.` operator is more restrictive: only tokens of name type can be used as
the attribute. Tokens of name type begins with an alphabet or an underscore and
can only contain alphanumeric and underscore characters.

One special attribute access rules for arrays is the ability to invoke closure
functions stored in arrays:

```php
<?php
$template = $flow->load('my_template.html');
$template->display(array(
    'user' => array(
        'firstname' => 'Rasmus',
        'lastname'  => 'Lerdorf',
        'fullname'  => function($self) {
            return $self['firstname'] . ' ' .  $self['lastname'];
        },
    ),
));
```

And call the `fullname` "method" in the template as follows:

    {{ user.fullname }}

When invoked this way, the closure function will implicitly be passed the array
it is in as the first argument. Extra arguments will be passed on to the closure
function as the second and consecutive arguments. This rule lets you have arrays
that behave not unlike objects: they can access other member values or functions
in the array.

## Helpers 

Helpers are simple functions you can use to test or modify values prior to use.
There are two ways you can use them:

- Using helpers as functions
- Using helpers as filters

Except for a few exception, they are exchangable.

### Using helpers as functions

    {{ upper(title) }}

You can chain helpers just like you would chain function calls in PHP:

    {{ nl2br(upper(trim(my_data))) }}

### Using helpers as filters

Use the `|` character to separate the data with the filter:

    {{ title | upper }}

You can use multiple filters by chaining them with the `|` character. Using them
this way is not unlike using pipes in Unix: the output of the previous filter is
the input of the next one. For example, to trim, upper case and convert newlines
to `<br>` tags (in that order), simply write:

    {{ my_data | trim | upper | nl2br }}

Some built-in helpers accept additional parameters, delimited by parentheses and
separated by commas, like so:

    {{ "foo " | repeat(3) }}

Which is equivalent to the following:

    {{ repeat("foo ", 3) }}

When using helpers as filters, be careful when mixing operators:

    {{ 12_000 + 5_000 | number_format }}

Due to operator precedence, the above example will be considered as:

    {{ 12_000 + (5_000 | number_format) }}

Which, when compiled to PHP, will yield 12005 which is probably not what you'd
expect. In this example, either put the addition inside parenthesis or use the
helper as a function.

### Special `raw` helper

The `raw` helper can only be applied as a filter. Its sole purpose is to mark an
expression as a raw string and should not be escaped even when autoescaping is
turned on.

### List of all available built-in helpers:

`abs`, `bytes`, `capitalize`, `cycle`, `date`, `dump`, `e`, `escape`, `first`,
`format`, `is_divisible_by`, `is_empty`, `is_even`, `is_odd`, `join`,
`json_encode`, `keys`, `last`, `length`, `lower`, `nl2br`, `number_format`,
`range`, `raw`, `repeat`, `replace`, `strip_tags`, `title`, `trans`, `trim`,
`truncate`, `unescape`, `upper`, `url_encode`, `word_wrap`.

### Registering custom helpers

Registering custom helpers is straightforward:

```php
<?php
$helpers = array(
    'random' => function() { return 4; },
    'exclamation' => function($s = null) { return $s . '!'; },
);
$flow = new Loader(array(
    'source'  => 'templates',
    'target'  => 'cache',
    'reload'  => true,
    'helpers' => $helpers,
));
$template = $flow->load('my_template.html');
$template->display();
```

Use your custom helpers just like any other built-in helpers:

    A random number: {{ random() }} is truly {{ "bizarre" | exclamation }}

As a rule, when used as a filter, the input is passed on as the first argument
to the helper. It's advisable to have a default value for every argument in your
custom helper.

Since built-in helpers and custom helpers share the same namespace, you can
override built-in helpers with your own version although it's not recommended.

## Branching

Use the `if` tag to branch. Use the optional `elseif` and `else` tags to have
multiple branches:

    {% if expression_1 %}
        expression 1 is true!
    {% elseif expression_2 %}
        expression 2 is true!
    {% elseif expression_3 %}
        expression 3 is true!
    {% else expression_4 %}
        expression 4 is true!
    {% endif %}

Values considered to be false are `false`, `null`, `0`, `'0'`, `''`, and `[]`
(empty array).

### Inline if and unless statement modifiers

Apart from the standalone block tag version, the `if` tag is also available as
a statement modifier. If you know Ruby or Perl, you might find this familiar:

    {{ "this will be printed" if this_evaluates_to_true }}

The above is semantically equivalent to:

    {%- if this_evaluates_to_true -%}
    {{ "this will be printed" }}
    {%- endif -%}

You can use any kind of boolean logic just as in the standard block tag
version:

    {{ "this will be printed" if not this_evaluates_to_false }}

Using the `unless` construct might be more natural for some cases.
The following is equivalent to the above:

    {{ "this will be printed" unless this_evaluates_to_false }}

### Ternary operator `?:`

You can use the ternary conditional operator if you need branching inside an
expression:

    {{ error ? '<p>' .. error .. '</p>' :  '<p>success!</p>' }}

The ternary operator has the lowest precedence in an expression.

## Iteration

Use `for` tags to iterate through each element of an array or iterator.
Use the optional `else` clause to implicitly branch if no iteration occurs:

    {% for link in links %}
        <a href="{{ link.url }}">{{ link.title }}</a> {% else %}
    {% else %}
        There are no links available.
    {% endfor %}

Empty arrays or iterators, and values other than arrays or iterators will branch
to the `else` clause.

You can also iterate as key and value pairs by using a comma:

    {% for key, value in associative_array %}
        <p>{{ key .. " = " .. value }}</p>
    {% endfor %}

Both `key` and `value` in the example above are local to the iteration. They
will retain their previous values, if any, once the iteration stops.

The reserved variable `loop` is available:

    {% for user in users %}
        {{ user }}{{ ", " unless loop.last }}
    {% endfor %}

The reserved `loop` variable has a few attributes:

- `loop.index`: The zero-based index.
- `loop.count`: The one-based index.
- `loop.first`: Evaluates to `true` if the current iteration is the first.
- `loop.last`: Evaluates to `true` if the current iteration is the last.
- `loop.parent`: The parent iteration `loop` object if applicable.

### break and continue

You can use `break` and `continue` to break out of a loop and to skip to the
next iteration, respectively:

    {# the following will print "1 2 3" #}
    {% for i in [1,2,3,4,5] %}
        {{ i }}
        {% break if i > 2 %}
    {% endfor %}

## Set

It is sometimes unavoidable to set values to variables; use the `set` construct:

    {% set fullname = user.firstname .. ' ' .. user.lastname %}

You can also use `set` as a way to buffer output and store the result in a
variable:

    {% set partial %}
    <p>This changes everything!</p>
    {% endset %}
    ...
    {{ partial }}
    ...

The scope of variables introduced by the `set` construct is global except when
used inside macros.

## Blocks

Blocks are at the core of template inheritance:

    {# this is in "parent_template.html" #}
    <p>Hello</p>
    {% block content %}
    <p>Original content</p>
    {% endblock %}
    <p>Goodbye</p>

    {# this is in "child_template.html" #}
    {% extends "parent_template.html" %}
    This will never be displayed!
    {% block content %}
    <p>This will be substituted to the parent template's "content" block</p>
    {% endblock %}
    This will never be displayed!

When child_template.html is loaded, it will yield:

    <p>Hello</p>

    <p>This will be substituted to the parent template</p>

    <p>Goodbye</p>

Block inheritance works by replacing all blocks in the parent, or extended
template, with the same blocks found in the child, or extending template, and
using the parent template as the layout template; the child template layout is
discarded. This works recursively upwards until there are no more templates to
be extended.

## Extends

The `extends` construct signals Flow to load and extend a template. Blocks
defined in the current template will override blocks defined in extended
templates:

    {% extends "path/to/layout.html" %}

The emplate extension mechanism is fully dynamic; you can use variables or wrap
it in conditionals just like any other statement:

    {% extends layout if some_condition %}

It is a syntax error to declare more than one `extends` tag per template or to
declare an `extends` tag anywhere but at the top level scope.

## Parent

By using the `parent` tag, you can include the parent block's contents inside
the child block:

    {% block child %}
        {% parent %}
    {% endblock %}

Using the `parent` tag anywhere outside a block or inside a macro is a syntax
error.

## Macro

Macros are a great way to make reusable partial templates:

    {% macro bolder(text) %}
    <b>{{ text }}</b>
    {% endmacro %}

To call them:

    {{ @bolder("this is great!") }}

All parameters are optional; they default to `null` while extra positional
parameters are ignored. You can also use named parameters:

    {{ @bolder(text="this is a text") }}

Extra named parameters overwrite positional parameters with the same name, and
are available inside the macro. The parentheses are optional only if there are
no arguments passed. All macro calls are prepended with the `@` character. This
is to avoid name collisions with helpers, method calls and attribute access.
Parameters and variables declared inside macros with the `set` construct are
local to the macro and will cease to exist once the macro returns.

Declaring macros anywhere but at the top-level scope in a template is a syntax
error.

### Importing macros

You generally would want to group macros in templates like you would functions
in modules or classes. To use macros defined in another template, simply import
them:

    {% import "path/to/form_macros.html" as form %}
    ...
    {{ @form.text_input }}
    ...

All imported macros must be aliased by using the `as` keyword. To call an
imported macro, simply prepend the macro name with the alias followed by a dot.

### Decorating macros

You can decorate macros by importing them first:

    {# this is in "macro_A.html" #}
    ...
    {% macro emphasize(text) %}<b>{{ text }}</b>{% endmacro %}
    ...


    {# this is in "macro_B.html" #}
    ...
    {% import "macro_A.html" as A %}
    ...
    {% macro emphasize(text) %}<i>{{ @A.emphasize(text) }}</i>{% endmacro %}
    ...


    {# this is in "template_C.html" #}
    ...
    {% import "macro_B.html" as B %}
    ...
    Emphasized text: {{ @B.emphasize("this is pretty cool!") }}

The above when rendered will yield:

    Emphasized text: <i><b>this is pretty cool!</b></i>

Decorating macros lets you effectively extend macros without the headache that
an inheritance mechanism can potentially induce.

## Include

Use the `include` tag to include bits and pieces of templates in your template:

    {% include "path/to/sidebar.html" if page.sidebar %}

This is usefull for things like headers, sidebars and footers. Including
non-existing or non-readable templates is a runtime error. Note that there are
no mechanism to prevent circular inclusion of templates although there is a
runtime limit on recursion.

## Output Escaping

You can escape data to be printed out by using the `escape` or its alias `e`
filter. Output escaping will only be applied once, no matter ho many times
you specify it in the filter chain. The `escape` and `e` helpers are only valid
if used as a filter.

### Using Autoescape

Use the auto escape facility if you want all expression output to be escaped
before printing, minimizing potential XSS attacks:

    {% autoescape on %}

Think of autoescape as implicitly putting an `escape` or `e` filter on every
expression output. You would normally want to put this directive somewhere near
the top of your template. Autoescape works on a per template basis; it is never
inherited from parent templates.

You do not need to worry if you accidentally double escape a variable. All data
already escaped will _not_ be autoescaped; this special case is why `escape` and
`e` can only be used as a filter and not a function:

    {% autoescape on %}
    {{ "Dr. Jekyll & Mr. Hyde" | escape }}

You can turn autoescape off any time by simply setting it to off:

    {% autoescape off %}

By default, autoescape is initially set to off.

### Raw Filter

By using the `raw` filter on a variable output, the data will *not* be escaped
regardless of any `escape` filters or the current autoescape status. Note that
just like `escape`, you must use it as a filter, using `raw` as a function call
will do nothing and will yield `null`.

## Controlling Whitespace

When you're writing a template for a certain file format that is sensitive
to whitespace, you can use `{%-` and `-%}` in place of the normal opening and
closing block tags to suppress whitespaces before and after the block tags,
respectively. You can use either one or both at the same time depending on
your needs. The `{{-` and `-}}` delimiters  are also available for expression
output tags, while the `{#-` and `-#}` delimiters are available for comment
tags.

The following is a demonstration of whitespace control:

    <ul>
        {%- for user in ["Alice", "Bob", "Charlie"] -%}
        <li>{{ user }}</li>
        {%- endfor -%}
    </ul>

Which will yield a compact

    <ul>
        <li>Alice</li>
        <li>Bob</li>
        <li>Charlie</li>
    </ul>

While the same example, this time without any white-space control:

    <ul>
        {% for user in ["Alice", "Bob", "Charlie"] %}
        <li>{{ user }}</li>
        {% endfor %}
    </ul>

Will yield the rather sparse

    <ul>
        
        <li>Alice</li>
        
        <li>Bob</li>
        
        <li>Charlie</li>
        
    </ul>

The semantics are as follows:

- `{%-`, `{{-`, and `{#-` delimiters will remove all whitespace to their left
  **up to but not including** the first newline.

- `-%}`, `-}}`, and `-#}` delimiters will remove all whitespace to their right
  **up to and including** the first newline.

## Raw Output

Sometimes you need to output raw blocks of text, as in the case of code. You
can use the raw tag:

    {% raw %}
    I'm inside a raw tag
    {% this will be printed as is. %}
    {% endraw %}

## License

Flow is released under the [MIT License][MIT].

## Acknowledgment

Flow is heavily based on the original Twig implementation by Armin Ronacher and
subsequently influenced by [Jinja2], Fabien Potencier's [Twig] fork, [Python],
and [Ruby].

[Chyrp]: http://chyrp.net/
[Composer]: http://getcomposer.org/
[MIT]: http://en.wikipedia.org/wiki/MIT_License
[Jinja2]: https://github.com/mitsuhiko/jinja2
[Twig]: https://github.com/fabpot/Twig
[Python]: http://www.python.org/
[Ruby]: http://www.ruby-lang.org/en/
