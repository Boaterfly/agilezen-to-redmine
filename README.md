agilezen-to-redmine
===================

## Usage
### 1. Installation
Assuming a Debian-based GNU/Linux OS.

```shell
sudo apt-get install composer
git clone https://github.com/Boaterfly/agilezen-to-redmine.git
cd agilezen-to-redmine
make
```

### 2. Export data from AgileZen.
```shell
./agilezen-to-redmine export export --agilezen-key=AGILEZEN-KEY
```

All projects, stories, and comments will be downloaded.  
You can obtain your AgileZen API key in the 'developer' section of the [AgileZen
settings](https://agilezen.com/settings).

### 2. Download attachments from AgileZen.
```shell
./agilezen-to-redmine download-attachments export --user=USER --password=PASSWORD
```

The AgileZen API can't be used to download attachments so an acual user has to
be spoofed.

## Notes
No effort was made to reduce memory consumption. Your whole AgileZen dataset will
be loaded multiple times in memory.  
No effort was made to reduce the number of files in a single directory. If you
have enough atachments to make your local FS break, congratulations.  
There is no way to fetch all attachments at once or to know if a story has
any, so **there will be one HTTP GET request per story**.
