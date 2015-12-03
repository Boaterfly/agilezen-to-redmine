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
./agilezen-to-redmine export --agilezen-key=AGILEZEN-KEY file.name
```

All projects, stories, and comments will be downloaded.  
You can obtain your AgileZen API key in the 'developer' section of the [AgileZen
settings](https://agilezen.com/settings).

## Notes
No effort is made to reduce memory consumption. Your whole AgileZen dataset will
be loaded multiple times in memory.  
There is no way to fetch all attachments at once or to know if a story has
any, so **there will be one HTTP GET request per story**.
