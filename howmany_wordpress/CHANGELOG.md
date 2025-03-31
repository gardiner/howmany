## 1.1.0 (31.03.2025)

* added refresh, adjusted is_current calculation
* unified different measurement components
* added hour resolution, specify default timescale in config
* updated build task, package-lock.json
* deleted obsolete file

## 1.0.0 (26.03.2025)

* updated composer.json/toolkit config
* Updated README
* limit measurement outputs to avoid delays
* prevent duplicate loading of initial values
* stop timeseries line today
* added interval pagination, caching of discrete measurements, readable labels
* started timescale handling
* exclude wordpress specific urls from tracking
* adjusted excludes
* cleanup – removed old charts and api
* added useragents, referers, urls, piecharts and lists
* added viewcounts and viewdurations
* added new measurement (visits)
* added first measurement (views)
* started implementation of generic measurements
* exclude howmany-urls from tracking
* extracted schema into store
* extracted API into class
* updated charting lib
* updated toolchain deps
* added ignore urls
* php cleanup
* restructured app to be more standards compliant
* moved config files into plugin directory
* added error handling to prevent request errors
* ignore some administrative requests
* minor fixes for current lodash version
