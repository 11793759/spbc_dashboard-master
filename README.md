# SPBC Dashboard

## Hosting

### Web Server

This repo contains the AppCode and htdocs for the GNA Dashboard. The server is hosted on [Intel CloudFoundry](https://cloudfoundry.intel.com/apps/signin). After signing in the first time contact Brian Kelley to receive access to the GNA-LIPID-Dashboard Application.

The current configuration:

- Org IAP: 31627
- Disk: 4096 MB
- Memory: 1024 MB
- Buildpack: php_buildpack
- Stack: cflinuxfs3
- App Code: points to this Git repo (https://gitlab.devtools.intel.com/dchauhan/spbc_dashboard.git)

Note: if there is a server error (Error 500), you can view the cause by navigating to the Cloud Foundry Portal and streaming the logs.

### Database

The database is a cloud-hosted MySQL (actually MariaDB - a free fork) on [Intel DBaaS](https://dbaas.intel.com/#/overview). Contact Brian Kelley for administrator access.

General database access is done in a read/write manner using LIPID - a Linux script. This script is kept in the GNA engineering Git repo. Regression and other scripts are layered on top to parse data and use underlying queries. Database management (table modification, etc.) is done using `lipid.sh query` - very carefully so as to not break anything. Before large changes please start a backup on DBaaS as a restore point.

	CREATE TABLE data (
		id INT AUTO_INCREMENT,
		date INTEGER NOT NULL,
		subject CHAR(250) NOT NULL,
		value TEXT,
		comment TEXT,
		PRIMARY KEY(id),
		INDEX(subject)
	);
	CREATE TABLE data_latest (
		id INT,
		date INTEGER NOT NULL,
		subject CHAR(250) NOT NULL,
		value TEXT,
		comment TEXT,
		PRIMARY KEY(subject)
	);
	CREATE TABLE files (
		filename VARCHAR(250) NOT NULL,
		mtime INTEGER,
		data LONGBLOB,
		PRIMARY KEY(filename)
	);
	CREATE TABLE regressions (
		name CHAR(250) NOT NULL,
		completed BOOLEAN,
		private BOOLEAN,
		start_date INTEGER NOT NULL,
		update_date INTEGER NOT NULL,
		cust CHAR(250) NOT NULL,
		model CHAR(250) NOT NULL,
		list CHAR(250) NOT NULL,
		data JSON,
		PRIMARY KEY(name),
		INDEX(list)
	);

`data` stores data inserted by `lipid.sh insert` without any de-duplication. `data_latest` is similar, but `subject` is a primary key so it only has one unique value per subject. This is an optimization to allow queries on `subject`s faster than in `data`. When inserting, LIPID inserts into both tables simultaneously.

`files` stores static web GZipped-TARs. See section below "Dynamic files" and `files.php`

### Files

#### Web files
Web files (PHP/HTML/etc.) are hosted in this Git repository and are downloaded by CloudFoundry when the Application is deployed. Typical flow to update the Dashboard is as follows:

1) Clone this repo locally: `git clone https://gitlab.devtools.intel.com/dchauhan/spbc_dashboard.git`
2) Edit the file and commit
3) `git push`
4) Navigate to http://spbc-dashboard.app.intel.com/gitpull.php
5) If you changed non-htdocs files, you must re-upload the application through CloudFoundry (point Git URL to here)

#### Dynamic files

Files like PSVsort reports and coverage reports were previously stored in Linux NFS and the webserver would access them via Samba, but in 2021ww7 that access was cut for security reasons. Since then, our "dynamic" files are stored in the MySQL database as BLOBs and are extracted by a PHP script when accessed.

* Uploaded by `lipid.sh upload <tar_name>.tar.gz`
* Stored in MySQL's `files` table with the tar_name, modification time, and the GZipped-TAR itself
* Accessing files
	* When a user's web browser accesses /files/(*), it is re-written to files.php?file=<accessed_file>
	* files.php parses the $file argument down until it can find a matching entry in the `files` table
	* Once found, it downloads that tar.gz BLOB, untars it to the server disk
	* files.php caches this data for ten minutes and serves all requests to that file (and all other fiels in that TAR) from disk
	* After ten minutes, files.php checks the database for any updated modification time. If it is modified, it downloads/untars again to retrieve the updated version
	* Every so often, the script will cleanup old files since our webserver only has 4GB of file space
* Note: a list of files can be found by navigating to /files/
	
## SPBC Dashboard (index.php)

### General Operation

In general, the PHP script's job is to grab data from the LIPID database and apply it to a template.

The templates are stored in Dashboard/. The PHP script looks through the `$values[$key]` hash and does the following string replacement: `s/\${$key}/$values[$key]/g`

### Modules

The logic of the Dashboard is split into Modules - classes with a simple `populate($mysql_handle, &$values, &$sections)` function that create blocks of output and stores them in `$sections`.

The output of `populate` is typically an insertion into `$sections`. `$sections[section_name]` is divided into three sections - TOP_BOXES (e.g. Pass Rate, QoV squares), MAIN (e.g. Regression Graph), and SIDEBAR (e.g. Config Information). Each `$sections[section_name][block_name]` is an associative array containing "weight", "template", and "values" (also an array). Weight specifies the order in that column/section (higher means higher up the page). The given template (relative to htdocs/) is filled in with the given values and inserted into the section of the template given by `section_name`.

### Information Table

The InformationTable is the set of rows of information about the currently-selected CONFIG. Any module can insert into that table like this:

	$values["INFO_ROWS"][sort_key] = array( "name" => "left column string", "value" => "right column value" );
	
The table sorts by the sort key and displays the "name" and "value" columns. This module is loaded/populated last so all other modules can add to INFO_ROWS in their populate methods.

### HTML Frameworks

The HTML template used is [AdminLTE](https://adminlte.io/docs/2.4/layout) which uses [Bootstrap](https://getbootstrap.com/docs/3.4/) for layout/CSS/JavaScript. Charts are created by [ChartJS](https://www.chartjs.org/docs/latest/).

### Regression Tables

Regression data is stored in the `regressions` MySQL database. Each regression has its own row, indexed by a unique name. Commonly-queried fields are MySQL columns, and it contains a `data` column that is a flexible JSON object. The intent of this is to containerize all details of a regression into one spot (as opposed to multiple keys when the data was stored in `data`).

The Dashboard retrieves entries primarily through the `get_regressions($where)` function. This retrieves all columns and the data field, merging them into one associative array for each matching regression.

Due to the nature of the command-line scripts that populate this data, early in the regression's lifecycle, some keys may not be populated. Unfortunately that means there is quite a lot of array_key_exists calls to ensure we do not access an undefined key.

### Subsections

Due to some slow loading graphs, the Regression Metrics graphs, Feature Health, and Regression Details sections have been broken off. They are loaded using jQuery in an asynchronous manner. Performance of these sub-pages has improved ten-fold since loading them asynchronously, but keeping them asynchronous really makes things feel responsive.

### Extra Flags

Many debug statements are hidden behind `&QUERY_DEBUG=1` - add it to the URL to see queries and other debug statements.