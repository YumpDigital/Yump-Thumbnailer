Yump Thumbnailer
================

The super-easy image thumbnailer (why aren't they all this easy to use?).

Before:

    <img src="/images/full-res-image.jpg">
    
After:

    <img src="/thumb/300x200/fit/image/full-res-image.jpg">
    
The first time a thumb is requested, the image will not be present so the `.htaccess` rule will trigger `create.php`
which generates the image and saves it in the correct location.  Future calls don't load PHP at all
(for best performance).

If the original image doesn't yet exist, it will also display a nice placeholder image until the image becomes valid, like this:

![Placeholder](http://placehold.it/350x150)

What if I want the image to be cropped to a specific size?
----------------------------------------------------------

    /thumb/200x100/fit/files/images/image.jpg
    /thumb/200x100/crop/files/images/image.jpg
    /thumb/200x100/cropFromLeft/files/images/image.jpg
    /thumb/200x100/cropFromRight/files/images/image.jpg
    /thumb/200x100/cropFromTop/files/images/image.jpg
    /thumb/200x100/cropFromBottom/files/images/image.jpg
              ^           ^          ^
              |           |          |
              |           |          Path under webroot to the original image
              |           |          
              |           Whether to resize image to fit *inside* those dimensions, or to crop to exact size
              |
              Size of thumbnail
    
    
OK, how do I install this thing?
--------------------------------

1. Create a `/thumb/` folder under the (public) root of your site.

2. [Download this repo](https://github.com/YumpDigital/Yump-Thumbnailer/archive/master.zip) and extract into your `/thumb/` folder

3. Start updating your image tags as per above

If your document root is in a non-standard place, you may need to update where the script looks for the images. Look for the following line in `create.php`:

    $_GET['src'] = '/' . $path;


Gotcha
------

Once a thumbnail is created, it will NEVER be recreated, unless the thumb is deleted. If you suspect that the original images may be updated at some point, you might need to think through a workaround for this (either delete the cached thumbnail or give the updated image a new name).


Credits
-------

By Simon East ([@SimoEast](https://twitter.com/SimoEast)), 2012


Licence
-------

Released under the MIT Licence
