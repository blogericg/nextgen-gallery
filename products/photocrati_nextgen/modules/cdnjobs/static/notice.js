(function() {
    // var is used here because using 'let' breaks the nextgen build process

    const initialize = function()
    {
        const wrapper       = document.getElementById('ngg_cdn_jobs_in_progress_notice');
        const details       = document.getElementById('ngg_cdn_jobs_in_progress_notice_details');
        const expand_link   = document.getElementById('ngg_cdn_jobs_in_progress_notice_expand_link');
        const minimize_link = document.getElementById('ngg_cdn_jobs_in_progress_notice_minimize_link');
        const refreshing    = document.getElementById('ngg_cdn_jobs_in_progress_notice_spinner');
        const countdown     = document.getElementById('ngg_cdn_jobs_in_progress_notice_countdown');

        const i18n   = ngg_cdn_jobs_in_progress_notice.i18n;

        var fetchedOnce  = false;
        var fetchedCache = null;
        
        async function checkServer() {
            countdown.classList.add('hidden');
            refreshing.classList.remove('hidden');

            timer_ticks = timer_runs;
            timer_pause = true;

            var response = await fetch(ngg_cdn_jobs_in_progress_notice.action, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-cache'
            });

            response     = await response.json();
            fetchedOnce  = true;
            fetchedCache = response;

            if (response.count > 0) {
                buildDetails(response.jobs);
                timer_pause = false;

                refreshing.classList.add('hidden');
                countdown.classList.remove('hidden');
            } else {
                refreshing.classList.add('hidden');
                countdown.classList.add('hidden');
                setDetailsFinished();
                document.getElementById('ngg_cdn_jobs_in_progress_notice_finished_link')
                        .addEventListener('click', function(event) {
                            event.preventDefault();
                            window.location = window.location;
                });
            }

            return response;
        }

        var timer_runs  = 10; // total ticks to perform
        var timer_ticks = 10; // ticks remaining
        var timer_pause = false;
        var timer = setInterval(function() {
            if (timer_pause) {
                return;
            }

            countdown.classList.remove('hidden');

            function setText(time) {
                countdown.innerHTML = i18n.refresh_countdown.replace('%d', time);
            }

            if (timer_ticks === 0) {
                timer_ticks = timer_runs;
                timer_pause = true;
                setText(timer_ticks);
                checkServer().then(function(response) {
                    setText(timer_ticks);
                });
            } else {
                setText(timer_ticks);
                timer_ticks--;
            }
        }, 1000);

        function setDetailsFinished() {
            wrapper.innerHTML = i18n.finished_label;
        }

        function expandDetails() {
            expand_link.classList.add('hidden');
            details.classList.remove('hidden');
        }

        function minimizeDetails() {
            expand_link.classList.remove('hidden');
            details.classList.add('hidden');
        }

        function buildDetails(jobs) {
            // Fill the <ul> with <li> of the pending jobs in the queue
            var list = details.getElementsByTagName('ul')[0];
            list.innerHTML = '';
            jobs.forEach(function(job) {
                var li = document.createElement('li');
                li.appendChild(document.createTextNode(job.label));
                list.appendChild(li);
            });
        }

        minimize_link.addEventListener('click', function(event) {
            event.preventDefault();
            minimizeDetails();
        });

        expand_link.addEventListener('click', function(event) {
            event.preventDefault();

            function handle(response) {
                if (response.count > 0) {
                    expandDetails();
                } else {
                    setDetailsFinished();
                }
            }

            if (fetchedOnce) {
                handle(fetchedCache);
            } else {
                checkServer().then(function(response) {
                    handle(response);
                });
            }

        });

    };

    document.addEventListener('DOMContentLoaded', initialize);
})();