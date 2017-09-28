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

TODO
----

- Add lots of other routes
- Document the API (routes, verbs, parameters)
- Propertly handle request and header content types
- Use HTTP headers for altering `external_settings` parameters
- Check caching-related response headers

License
-------

Licensed under the [GNU GPL License](http://www.gnu.org/copyleft/gpl.html).
