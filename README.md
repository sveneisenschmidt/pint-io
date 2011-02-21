**pint**
*n.* The [pint](http://en.wikipedia.org/wiki/Pint "Explanation on wikipedia") is an English unit of volume or capacity.

pint.IO - Let's have a pint and serve lots of HTTP requests!
=

##Features
* Forkable Server with support for Workers
* SapiAdapter for native PHP scripts and small applications
  * Use die & exit like you ar ein a normal apache or nginx, pint.IO server will not stop
    execution when you are working with workers
  * $\_FILES, $\_GET, $\_POST, $\_REQUEST, $\_SERVER, ($\_COOKIES - not yet implemented) is there like you would expect
