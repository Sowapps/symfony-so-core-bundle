# Saw

Saw is the template js rendering system on JS side.
This feature is composed of 3 parts:

- The sawService for more app specific features like download template from server, set an element loading...
- The domService is using to manipulate the DOM, it also renders the template from HTML Template to String to HTML Element.
- The StringTemplate takes a template string and resolves values inside. It's able to apply filters brought by domService.

String template is processing first, we called it STRING_TEMPLATE.
Then we render the HTML template, we called it DOM_TEMPLATE.

## Features in templates

### Boolean attributes

The browser won't set a boolean attribute to false if its value is false, so we replace attribute `"false"` by the property `false`.  
The list of attributes considered has boolean is determined : "disabled", "checked", "readonly".
Processor: DOM_TEMPLATE.

### Filtering value

Any template string is surrounded by {}, e.g. {createDate}
DomService set a list of filters.

A filter can be differentiated using parentheses even if there is no argument.

```
{createDate|date()}
```

#### Property chain

Property chain is processed as filter of the data.

If data contains an object with key `user` and this object contains a key `id`.

```
{user.id}
```

#### Scalar string

Scalar string is identified if there is quotes, both double or single quotes.

```
{'string'}
```

(This is more useful as filter argument)

#### Scalar integer

Scalars are identified if this is an unquoted integer. The returned value is an integer.

```
{999}
```

(This is more useful as filter argument)

#### Filter default

Return first parameter if the value is false. This is the opposite filter of `then`.

```
{createDate|default('-')}
```

#### Filter truncate

Truncate the string value to the length given by the first argument.  
Arguments:

- length: The length as integer.
- ellipsis: The ending suffix as string when the string is truncated.

```
{description|truncate(20)}
```

#### Filter date

Format date to locale string.

```
{createDate|date}
```

#### Filter contains

Return true if the value is an array containing the first argument.

```
{grantedRoles|contains('ROLE_USER')}
```

#### Filter then

Return first argument if value is true. This is the opposite filter of `default`.

```
{enabled|then('Yes')}
```

#### Filter upper

Format value to uppercase.

```
{label|upper}
```

#### Filter attr

Escape value for an HTML attribute.

```
{label|attr}
```

#### Filter length

Return the length of an array.

```
{label|length}
```

#### Filter join

Join array values using separator.

```
{roles|join(', ')}
```

#### Filter url_host

Extract host from url value.

```
{url|url_host}
```
