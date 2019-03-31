# tadpoles-api
A PHP script which pretends to be the Tadpoles.com iPhone app and downloads/backs-up all of your kids' photos and videos from daycare.

## Motivation

Both of my kids attend an awesome daycare M-F. The school uses the (mostly awful) [Tadpoles](https://www.tadpoles.com) service to communicate with parents and send any photos/videos they take of our kids throughout the day.

Tadpoles' iOS app is awful - I mean, it works, mostly - but its made (poorly) with [Titanium](https://www.appcelerator.com/) and decidedly not-native and very crashy. So, years ago, I stopped using the app and opted-in to receive my kids' updates via email.

Anytime we'd receive a particularly good photo of our kid, we'd save it to our phone for posterity and for sharing with the grandparents. But, Tadpoles stops you from saving any photo that contains _any other child_ besides your own (for privacy reasons I assume), which means you lose all of the fun shots with friends. Also, you can't save videos at all.

I'm obsessive about backing up my family's photos and home videos, so I wrote [a little script](https://gist.github.com/tylerhall/f19e78829fcd6babb301a6f3c9b90375) that automatically downloads any new ones Tadpoles sends. [More info here](https://tyler.io/fixing-a-broken-service-with-a-tiny-bit-of-automation/).

It works great for new items. But I started thinking about all the old pictures/videos that I never bothered to save. So I started looking through my email archives for old Tadpoles updates and discovered that the media attachments _expire_ after three days. At first I was devastated to think I'd lost all those memories, but then my wife, who _does_ use the iOS app, told me they're still viewable in the app. Aha!

So, I spent an evening digging through the app and figuring out how the Tadpoles API works. The result is this repo. It's just one script that focuses on backing up all of your kids' photos and videos no matter how long ago they were taken. If you need to do something beyond that, there are hooks in place to let you extend the script to make whichever additional API calls you need to do what you want.

Also, if you have the appropriate tools installed on your system, the script will set the photo/video's EXIF creation date and filesystem modification date. This makes your items play nicely and sort properly if you archive then into Google Photos or iCloud.

## Installation

**Requirements**

* macOS or other Unix-y like system.
* PHP 5.4 or greater with the curl extension installed.

**Setup**

1. Clone this repo somewhere, or just download the [`tadpoles-backup.php`](https://github.com/tylerhall/tadpoles-api/blob/master/tadpoles-backup.php) script. The whole project is just one file - no dependencies.
2. Fill in the email address and password for your Tadpoles account at the top of `tadpoles-backup.php`.
3. Add your children's first names so the script can ignore items of other children. (Note: the Tadpoles API _does not_ return items from other children besides your own - don't worry. But, they _do_ return group shots containing your kids where another child is the focus. We use your kids' first names to ignore these types of items.) If you want to archive _all_ items no matter what, you can modify the script to suit your needs.
4. Set a path to a folder in `$absolute_destination_folder` where you want the script to save your items. This folder must exist before running the script.
5. Call the `download_all_attachments()` function for each month you want to backup.
6. Make the script executable and run it with `./tadpoles-backup.php` or `php tadpoles-backup.php`.

All of the photos/videos for the month you specified will be saved into your folder with the following filename format:

    YYYY-mm-dd HH.mm.ss - Tadpoles - KidName.jpg

or

    YYYY-mm-dd HH.mm.ss - Tadpoles - KidName.mp4

Note: the script only handles JPGs (and PNGs pretending to be JPGs) and MP4s. Those are the only types of files that were ever returned for my children when testing. If you're seeing something else, please file a bug or pull request.

**Optional Setup**

The script can optionally set the EXIF date of your items and also convert PNG files returned by Tadpoles into JPGs so the date can be set on them, too. (Occasionally, the Tadpoles API will return a JPG file which is actually a PNG. It's dumb. But the script will handle that case and convert the file for you.)

To use these optional features, you need to have [ExifTool](https://sno.phy.queensu.ca/~phil/exiftool/) and [ImageMagick](https://www.imagemagick.org/) installed on your system and in your `$PATH`.

On Debian/Ubuntu systems, that's simple to do with `sudo apt-get install imagemagick exiftool`. On macOS, you can use [Homebrew](https://brew.sh/). Both tools are readily available for any other system you might be running.

## Notes

I have no idea what sort of infrastructure Tadpoles' API is running on - it looks to maybe be in Google's Cloud. So, play nice and don't abuse their API too heavily. That said, the official iOS app itself is _terribly noisy_ and makes way more API calls than it should be on its own, so anyone running this script is probably going to be a nicer API citizen than their own app.
