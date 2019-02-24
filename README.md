This *Cache Scheduler* is a library that allows you to vary your cache TTLs according to a self defined schedule. I've written [a little blog post on why you might want to consider that](https://dev.to/erikbooij/getting-the-most-out-of-server-side-caching-35o7).

Usage is simple:
 
1. install it with Composer:

    ```bash
    $ composer require erikbooij/cache-scheduler
    ```
2. Create a schedule and scheduler:
    ```php
    $schedule = (new Schedule)
        ->requireUpToDateDataFrom(Schedule::MON, 8, 0)
        ->allowStaleDataFrom(Schedule::MON, 17, 30)
        ->requireUpToDateDataFrom(Schedule::TUE, 8, 0)
        ->allowStaleDataFrom(Schedule::TUE, 17, 30)
        ->requireUpToDateDataFrom(Schedule::WED, 8, 0)
        ->allowStaleDataFrom(Schedule::WED, 17, 30)
        ->requireUpToDateDataFrom(Schedule::THU, 8, 0)
        ->allowStaleDataFrom(Schedule::THU, 17, 30)
        ->requireUpToDateDataFrom(Schedule::FRI, 8, 0)
        ->allowStaleDataFrom(Schedule::FRI, 17, 30);
    
    // Create the scheduler, passing it a SystemClock instance to interface with system time
    $scheduler = (new Scheduler(new SystemClock))
        ->setSchedule($schedule)
        ->setExpirationSpread(ExpirationSpread::minutes(30));
    ```
3. Use the scheduler to determine the allowed lifespan of your item in cache:
    ```php
    $cache->set('cache-key', 'cache-value', $scheduler->calculateTimeToLive(3600));
    ```
    
The number passed as the first argument to `->calculateTimeToLive()` is the cache TTL if your data is currently require to be up-to-date (the default TTL).    

Instead of attaching the schedule and expiration spread to the scheduler as in the example, you can also pass that as the second and third argument respectively to `->calculateTimeToLive()`. If you use both, the method arguments will override the values attached to the scheduler.
