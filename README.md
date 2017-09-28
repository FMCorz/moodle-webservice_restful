RESTful API
===========

Webservice protocol plugin for Moodle enabling developers to use a RESTful API.

Introduction
------------

The default protocols included in Moodle are RPC-like protocols, even the `rest` one, and thus do not use verbose URLs, HTTP methods or HTTP status codes. This plugin attempts to remedy this by mapping URLs and HTTP methods to existing external functions.

```
GET /webservice/restful/index.php/courses/2

HTTP/1.1 200 OK
Content-Length: 4211
Content-Type: application/json

{
    "id": 2,
    "fullname": "An Awesome Course",
    "shortname": "AwesomeCourse",
    ...
}
```

Pre-requisites
--------------

This version has only been tested with the following but could work in different configurations.

- Moodle 3.4
- PHP 7.0

Installation
------------

__NOT PRODUCTION READY:__ This plugin is yet in alpha, do not use it in production!

* Place the content of this repository in the folder `webservice/restful`.
* Navigate to `Site Administration > Notifications` to install the plugin.

The API
-------

The root of all the following is `webservice/restful/index.php`.

| Route                 | Method | Comment             |
|-----------------------|--------|---------------------|
| /courses              | GET    | List all courses    |
| /courses              | POST   | Create a new course |
| /courses/ID           | GET    | Get a course        |
| /courses/ID           | PATCH  | Update a course     |
| /courses/ID           | DELETE | Delete a course     |
| /courses/ID/duplicate | POST   | Duplicate a course  |

How to use
----------

Enable everything needed as for any other webservice protocol, and create a token.

* The requests are made at `/webservice/restful/index.php/API_ROUTE`
* The header `Content-Type` should read `application/json`
* The request body must be in JSON
* The token must be passed using the header `Authorization: Bearer TOKEN`

### Example GET:

```
GET /webservice/restful/index.php/courses/2 HTTP/1.1
Accept: */*
Authorization: Bearer 10787a782d5cea26d69e103729d594f7
Host: localhost


HTTP/1.1 200 OK
Connection: Keep-Alive
Content-Length: 4211
Content-Type: application/json
Date: Thu, 28 Sep 2017 11:47:55 GMT
Keep-Alive: timeout=5, max=100
Server: Apache/2.4.25 (Ubuntu)

{
    "id": 2,
    "fullname": "An Awesome Course",
    "shortname": "AwesomeCourse",
    ...
}
```

With [HTTPie](https://httpie.org):

```
http https://example.com/webservice/restful/index.php/courses/2 Authorization:'Bearer 10787a782d5cea26d69e103729d594f7'
```

With curl:

```
curl localhost/ws/webservice/restful/index.php/courses/2 -H 'Authorization: Bearer 10787a782d5cea26d69e103729d594f7'
```

### Example POST

```
POST /webservice/restful/index.php/courses HTTP/1.1
Accept: application/json, */*
Accept-Encoding: gzip, deflate
Authorization: Bearer 10787a782d5cea26d69e103729d594f7
Content-Length: 89
Content-Type: application/json

{
    "categoryid": "1",
    "fullname": "Another Awesome Course",
    "shortname": "AnotherAwesome"
}

HTTP/1.1 201 Created
Connection: Keep-Alive
Content-Length: 39
Content-Type: application/json
Date: Thu, 28 Sep 2017 11:54:17 GMT
Keep-Alive: timeout=5, max=100
Server: Apache/2.4.25 (Ubuntu)

{
    "id": 12,
    "shortname": "AnotherAwesome"
}
```

With HTTPie:

```
http POST localhost/ws/webservice/restful/index.php/courses \
    Authorization:'Bearer 10787a782d5cea26d69e103729d594f7' \
    fullname="Another Awesome Course" \
    shortname="AnotherAwesome" \
    categoryid=1
```

With curl:

```
curl -X POST localhost/ws/webservice/restful/index.php/courses \
    -H "Authorization: Bearer 10787a782d5cea26d69e103729d594f7" \
    -H 'Content-Type: application/json' \
    -d '{"fullname": "Another Awesome Course", "shortname": "AnotherAwesome", "categoryid": 1}'

```

Development
-----------

This plugin is still in a very early stage, and thus the API design could drastically change by the time it is deemed production ready. Also note that performance have not been considered at all at this stage. However, if you are interested in learning how to define a new route, here is some documentation.

The plugin works by analysing the URL and mapping it to a route. A route is constituted of a regex, and a list of methods that can be handled. Each method contains a handler function which is responsible for returning a response.

There are no interfaces, classes, or strong types defined. Rather functions are used to construct data in the expected format. For instance, a response will be an associative array containing the response code, its text and maybe some data. To construct such a response, you would use the function `make_response`, or a more appropriate one such as `content_created`.

### Quick tutorial

Let's create a new route to get a category.

```
[
    'regex' => '/categories/([0-9]+)',
]
```

Notice that the regex does not include the usual start and end delimiters (`^$`), those will be added automatically later. And notice that we are capturing the category ID.

Now, we will add the `GET` method, which will use the existing external function `core_course_get_categories`. There is a function to help us create a pipeline for handling requests going through to the external API: `external_api_method`. Its first argument is the name of the external function, its second argument will be an associative array containing modifying functions, but let's leave that aside for now.

```
[
    'regex' => '/categories/([0-9]+)',
    'methods' => [
        'GET' => external_api_method('core_course_get_categories')
    ]
]
```

If we test this like this, and request `/categories/1`, the function will return all the categories. That is because the external function `core_course_get_categories` did not receive any parameter, in which case it returns all the categories. How do we map the ID from the route to the call of the external function then? By using the `argsmapper` function to provide to `external_api_method`. In `argsmapper` you will construct the arguments expected by the external function.

The function `core_course_get_categories` expects an array containing `criteria` under which there are rows of criterion, each containing `key` and `value`, for us it looks a bit like this:

```
[
    'criteria' => [
        [
            'key' => 'id',
            'value' => CATEGORY_ID
        ]
    ]
]
```

So let's add our `argsmapper` function:

```
[
    'regex' => '/categories/([0-9]+)',
    'methods' => [
        'GET' => external_api_method('core_course_get_categories', [
            'argsmapper' => function($routeargs, $request, $options) {
                return [
                    'criteria' => [
                        [
                            'key' => 'id',
                            'value' => $routeargs[0]
                        ]
                    ]
                ];
            }
        ])
    ]
]
```

Notice that the `argsmapper` function receives three parameters: the route arguments from our regex, and the request and options which we will not cover now. It's important that you note that the `$routeargs` are indexed from 0, not 1.

And now it works!

```
GET /categories/1

HTTP/1.1 200 OK

[
    {
        "id": 1,
        "name": "Miscellaneous",
        ...
    }
]
```

However, there is still a quirk here. The category is returned in the form of an array of categories, that is not what we want when we only expect to get one. Also, when we request a category that does not exist, we still get a `200 OK` with an empty array.

```
GET /categories/123456

HTTP/1.1 200 OK

[]
```

So, what we need to do now, is to add the `resultmapper` function. Its purpose is to manipulate the result in order to return what we need, the same way we mapped the arguments to the function. It will receive the same arguments as `argsmapper`, except that it will also receive the result. Here is how it looks like.

```
[
    'regex' => '/categories/([0-9]+)',
    'methods' => [
        'GET' => external_api_method('core_course_get_categories', [
            'argsmapper' => function($routeargs, $request, $options) {
                return [
                    'criteria' => [
                        [
                            'key' => 'id',
                            'value' => $routeargs[0]
                        ]
                    ]
                ];
            },
            'resultmapper' => function($result, $args, $request, $options) {
                return reset($result) ?: null;
            }
        ])
    ]
]
```

That's it! Now `/categories/1` just returns the category object. And when we request the category that does not exist, we get a `404 Not Found` response without a body. Oh, and if you are wondering what `?: null` means, well that's simply to ensure that the return value is `null` when `$result` is empty. When `null` is returned from a `GET` request, `external_api_method` automatically assumes that it is a `Not Found`.

### Before you ask

> Are you validating the parameters?

Yes, the function `external_api_method` takes care of validating both the parameters, and the return values of the external functions.

> What if I want to return another status code?

You can define the function `responsemaker` and return your own reponse.

> Can I capture exceptions raised by the external function?

The function `errorhandler` can be specified to handle different type of responses based on the exceptions captured from the external function execution. Exceptions triggered at other stages of the request will be handled separately.

TODO
----

- Add lots of other routes
- Write tests, lots of tests
- Document the API (routes, verbs, parameters)
- Propertly handle request and header content types
- Use HTTP headers for altering `external_settings` parameters
- Check caching-related response headers

License
-------

Licensed under the [GNU GPL License](http://www.gnu.org/copyleft/gpl.html).
