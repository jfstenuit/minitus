# minitus
## _A very barebone implementation of TUS in PHP_

Resumable file upload in PHP  using [tus resumable upload protocol v1.0.0](https://tus.io)

**tus** is a HTTP based protocol for resumable file uploads. Resumable means you can carry on where you left off without re-uploading whole data again in case of any interruptions. An interruption may happen willingly if the user wants to pause, or by accident in case of a network issue or server outage.

Clearly inspired by [tus-php](https://github.com/ankitpokhrel/tus-php) from which the example "index.html" is borrowed.

## Installation

Copy tus-server.php and .htaccess in a directory
Create an "upload" directory that is writeable by the web server user

That's all !

Tested on Linux with Apache and mod_php. You may need to adapt the set-up if running with NGinX/Lighttpd and/or FastCGI. The principle is that the script is called as an handler for any request to a specific URI.

## Security

**Do not use in production**

With the current code, anybody can upload anything on the server (including PHP code that can be interpreted). You **must** adapt the code to implement appropriate controls (access control, file sanity checks, antimalware, ...)

## Notes

### On running behind a reverse proxy

Most client-side JavaScript implementation of TUS (including [Uppy](https://uppy.io/) - which is used in the demo environment) advise to use the default [chunkSize](https://github.com/tus/tus-js-client/blob/main/docs/api.md#chunksize) of "Infinity".

I would strongly recommend not to use this setting as this can cause overflows in your reverse proxy infrastructure. I propose instead a chunk size that will enable each chunk to be uploaded in 5 to 10 seconds. With today's broadband upload speeds, this translate to roughly 5MB chunks.
