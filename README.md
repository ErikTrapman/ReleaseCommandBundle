ReleaseCommandBundle
====================

Prepares a release of a standard Symfony2-project by copying all files needed to a given path.
Useful when you have to FTP all of your files individually to a shared-hosting environment.

Please note, this script does not support the use of git-submodules, simply because it uses git-archive, and this does not work with git-submodules.

Since this script does use git-archive, you might want to double check if you're using .gitattributes (http://git-scm.com/book/ch7-2.html) to prevent .git* files ending up on your webserver.


TODO
===================
* Support use of git-submodules?
* Windows-support needed? (not even tested yet)
* Optionally gzip or tar the whole pack of release files
* ?
