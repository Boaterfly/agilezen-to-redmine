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
./agilezen-to-redmine export --agilezen-key=KEY
```

All projects, stories, and comments will be downloaded.  
You can obtain your AgileZen API key in the 'developer' section of the [AgileZen
settings](https://agilezen.com/settings).

### 3. Download attachments from AgileZen.
```shell
./agilezen-to-redmine download-attachments --user=USER --password=PASSWORD
```

The AgileZen API can't be used to download attachments so an acual user has to
be spoofed.  
**Do not log back in AgileZen** while files are being downloaded, AgileZen only
allows one session per user.

You may need to change the maximum attachment size in Redmine settings.

### 4. Map AgileZen phases to Redmine statuses.
```shell
./agilezen-to-redmine map-phases-to-statuses --redmine-url=URL --redmine-key=KEY
```

### 5. Import into Redmine
**Disable Redmine mail notifications before this step or you will spam every
user with a mail for every action he ever did.**

```shell
./agilezen-to-redmine import --redmine-url=URL --redmine-key=KEY
```

Don't forget to turn notifications back on.

## Notes
* No effort was made to reduce memory consumption. Your whole AgileZen dataset
  will be loaded multiple times in memory.
* No effort was made to reduce the number of files in a single directory. If
  you have enough attachments to break your local filesystem, congratulations.
* There is no way to fetch all attachments at once or to know if a story has
  any, so **there will be one HTTP GET request per story** plus one per
  attachment.
* Requests are done synchronously and sequentially.
* There is an `--output-dir` option set to `export` by default. Change this if
  you want your data to be exported elsewhere.
