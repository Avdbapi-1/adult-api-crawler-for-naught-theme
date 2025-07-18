(function ($) {
    "use strict";

    $(function () {
        // DOM elements
        const buttonCheckApi = $("#api-check");
        const buttonRollCrawl = $("#roll-crawl");
        const buttonUpdateCrawl = $("#update-crawl");
        const buttonFullCrawl = $("#full-crawl");
        const buttonPageFromTo = $("#page-from-to");
        const buttonSelectedCrawl = $("#selected-crawl");
        const buttonPauseCrawl = $("#pause-crawl");
        const buttonResumeCrawl = $("#resume-crawl");
        const buttonOneCrawl = $("#onemovie-crawl");
        const alertBox = $("#alert-box");
        const moviesListDiv = $("#movies-list");
        const divCurrentPage = $("#current-page-crawl");
        const inputPageFrom = $("input[name=page-from]");
        const inputPageTo = $("input[name=page-to]");
        const contentSection = $("#content");
        const statsSection = $("#stats-section");
        const crawlingSection = $("#crawling-section");

        // Variable
        let latestPageList = [];
        let fullPageList = [];
        let pageFromToList = [];
        let tempPageList = [];
        let tempMoviesId = [];
        let tempMovies = [];
        let tempHour = "";
        let apiUrl = "";
        let isStopByUser = false;
        let maxPageTo = 0;

        // Add a variable to track the current movie being processed
        let currentMovie = null;

        // Helper function to show/hide sections
        function showSection(section) {
            section.removeClass('hidden');
        }

        function hideSection(section) {
            section.addClass('hidden');
        }

        // Helper function to update button states
        function updateButtonStates(enabled = false) {
            buttonRollCrawl.prop("disabled", !enabled);
            buttonUpdateCrawl.prop("disabled", !enabled);
            buttonFullCrawl.prop("disabled", !enabled);
            buttonPageFromTo.prop("disabled", !enabled);
            buttonSelectedCrawl.prop("disabled", !enabled);
            
            // Enable pause/resume buttons during crawling
            if (!enabled) {
                buttonPauseCrawl.prop("disabled", false);
                buttonResumeCrawl.prop("disabled", true);
            } else {
                buttonPauseCrawl.prop("disabled", true);
                buttonResumeCrawl.prop("disabled", true);
            }
        }

        // Helper function to show a Bootstrap modal for all messages
        function showModal(type, message) {
            let title = 'Message';
            let headerClass = '';
            if (type === 'danger') { title = 'Error'; headerClass = 'bg-danger text-white'; }
            else if (type === 'warning') { title = 'Warning'; headerClass = 'bg-warning text-dark'; }
            else if (type === 'success') { title = 'Success'; headerClass = 'bg-success text-white'; }
            else if (type === 'info') { title = 'Info'; headerClass = 'bg-info text-white'; }
            // Set modal title and body
            const modalTitle = document.getElementById('globalMessageModalLabel');
            const modalBody = document.getElementById('globalMessageModalBody');
            if (modalTitle) {
                modalTitle.textContent = title;
                modalTitle.className = 'modal-title ' + headerClass;
            }
            if (modalBody) {
                modalBody.innerHTML = message;
            }
            // Show the modal (Bootstrap 5)
            const modal = new bootstrap.Modal(document.getElementById('globalMessageModal'));
            modal.show();
        }

        // Helper function to show alerts
        function showAlert(message, type = 'info') {
            alertBox.removeClass().addClass(`alert alert-${type}`);
            alertBox.html(message);
            alertBox.removeClass('hidden');
        }

        function hideAlert() {
            alertBox.addClass('hidden');
        }

        // Helper function to show errors as both inline alert and popup
        function showError(message) {
            showModal('danger', "❌ " + message);
        }

        // Helper function to update loading state
        function setLoadingState(button, loading = true) {
            const spinner = button.find('.spinner-border');
            if (loading) {
                spinner.removeClass('hidden');
                button.prop('disabled', true);
            } else {
                spinner.addClass('hidden');
                button.prop('disabled', false);
            }
        }

        // Initialize - disable all buttons
        updateButtonStates(false);

        // Check input api first
        buttonCheckApi.click(function (e) {
            e.preventDefault();
            apiUrl = $("#jsonapi-url").val();
            if (!apiUrl) {
                showError("API URL cannot be empty. Please enter a valid AVDBAPI URL.");
                return false;
            }

            // Clear previous data
            $("#movies-table tbody").html("");
            hideSection(moviesListDiv);
            hideSection(contentSection);
            hideSection(statsSection);
            hideSection(crawlingSection);
            hideAlert();

            setLoadingState($(this), true);
            $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Checking API...`);

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_crawler_api",
                    api: apiUrl,
                },
                success: function (response) {
                    setLoadingState(buttonCheckApi, false);
                    buttonCheckApi.html(`Check API Connection`);
                    
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        showModal('danger', `❌ ${data.message}`);
                    } else {
                        showModal('success', `✅ API connection successful! Found ${data.total} total pages.`);
                        
                        // Enable all sections
                        showSection(contentSection);
                        showSection(statsSection);
                        showSection(crawlingSection);
                        const crawlSection = document.getElementById('crawling-section');
                        if (crawlSection) {
                            crawlSection.scrollIntoView({ behavior: 'auto', block: 'start' });
                        }
                        
                        // Update statistics
                        $("#last-page").html(data.last_page);
                        $("#per-page").html(data.per_page);
                        $("#movies-total").html(data.total);
                        
                        // Enable buttons
                        updateButtonStates(true);
                        
                        // Store data
                        latestPageList = data.latest_list_page;
                        fullPageList = data.full_list_page;
                        maxPageTo = data.last_page;
                        
                        // Auto-fill page range for convenience
                        inputPageFrom.val(1);
                        inputPageTo.val(Math.min(10, data.last_page));
                    }
                },
                error: function() {
                    setLoadingState(buttonCheckApi, false);
                    buttonCheckApi.html(`Check API Connection`);
                    showError("Failed to connect to API. Please check your URL and try again.");
                }
            });
        });

        // Crawl one movie (if exists)
        if (buttonOneCrawl.length) {
            buttonOneCrawl.click(function (e) {
                e.preventDefault();
                let oneLink = $("#onemovie-link").val();
                if (!oneLink) {
                    showError("Movie link cannot be empty.");
                    return false;
                }
                
                setLoadingState($(this), true);
                $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Collecting...`);
                
                $.ajax({
                    type: "POST",
                    url: ajaxurl,
                    data: {
                        action: "avdbapi_crawler_link_api",
                        api: oneLink,
                    },
                    success: function (response) {
                        let data = JSON.parse(response);
                        if (data.code > 1) {
                            showModal('danger', `❌ ${data.message}`);
                        } else {
                            showModal('success', `✅ ${data.message}`);
                        }
                        setLoadingState(buttonOneCrawl, false);
                        buttonOneCrawl.html("Collect immediately");
                    },
                    error: function (xhr, ajaxOptions, thrownError) {
                        showError("Something went wrong. Please try again.");
                        setLoadingState(buttonOneCrawl, false);
                        buttonOneCrawl.html("Collect immediately");
                    },
                });
            });
        }

        // Set page from to
        buttonPageFromTo.click(function (e) {
            e.preventDefault();
            hideAlert();
            
            let pageFrom = parseInt(inputPageFrom.val());
            let pageTo = parseInt(inputPageTo.val());
            
            if (
                pageTo > maxPageTo ||
                pageFrom > pageTo ||
                pageFrom <= 0 ||
                pageTo <= 0 ||
                pageFrom == null ||
                pageTo == null
            ) {
                showError(`Invalid page range. Please enter valid page numbers between 1 and ${maxPageTo}.`);
                return;
            }
            
            let pages = [];
            for (let i = parseInt(pageFrom); i <= pageTo; i++) {
                pages.push(i);
            }
            pageFromToList = pages;
            
            showModal('success', `✅ Page range set successfully: ${pageFrom} to ${pageTo} (${pages.length} pages)`);
        });

        // Crawl from pageFrom to pageTo
        buttonSelectedCrawl.click(function (e) {
            e.preventDefault();
            if (pageFromToList.length === 0) {
                showModal('warning', 'Please set a page range first.');
                // Scroll to Page Range Configuration section
                const pageRangeSection = document.getElementById('content');
                if (pageRangeSection) {
                    pageRangeSection.scrollIntoView({ behavior: 'auto', block: 'start' });
                }
                return;
            }
            
            showSection(moviesListDiv);
            setLoadingState($(this), true);
            $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Starting...`);
            crawl_movies_page(pageFromToList, "");
        });

        // Update today's movies
        buttonUpdateCrawl.click(function (e) {
            e.preventDefault();
            showSection(moviesListDiv);
            setLoadingState($(this), true);
            $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Starting...`);
            crawl_movies_page(latestPageList, 24);
        });

        // Crawl full movies
        buttonFullCrawl.click(function (e) {
            e.preventDefault();
            showSection(moviesListDiv);
            setLoadingState($(this), true);
            $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Starting...`);
            crawl_movies_page(fullPageList, "");
        });

        // Random crawl page
        buttonRollCrawl.click(function (e) {
            e.preventDefault();
            setLoadingState($(this), true);
            $(this).html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Randomizing...`);
            
            // Simulate processing time
            setTimeout(() => {
                fullPageList.sort((a, b) => 0.5 - Math.random());
                latestPageList.sort((a, b) => 0.5 - Math.random());
                pageFromToList.sort((a, b) => 0.5 - Math.random());
                
                setLoadingState($(this), false);
                $(this).html("✅ Order Randomized");
                
                showModal('success', "✅ Crawling order has been randomized successfully!");
            }, 1000);
        });

        // Pause crawl
        buttonPauseCrawl.click(function (e) {
            e.preventDefault();
            isStopByUser = true;
            buttonResumeCrawl.prop("disabled", false);
            buttonPauseCrawl.prop("disabled", true);
            showModal('info', "⏸️ Crawling paused. Click 'Resume' to continue.");
        });

        // Resume crawl
        buttonResumeCrawl.click(function (e) {
            e.preventDefault();
            isStopByUser = false;
            buttonPauseCrawl.prop("disabled", false);
            buttonResumeCrawl.prop("disabled", true);
            showModal('success', "▶️ Crawling resumed.");
            
            // Resume from the appropriate level
            if (currentMovie) {
                // Resume the in-progress movie
                crawl_movie_by_id(tempMoviesId, tempMovies, false, currentMovie);
            } else if (tempMoviesId && tempMoviesId.length > 0) {
                // Resume at movie level
                crawl_movie_by_id(tempMoviesId, tempMovies, false);
            } else if (tempPageList && tempPageList.length > 0) {
                // Resume at page level
                crawl_movies_page(tempPageList, tempHour);
            }
        });

        // Crawl movies page
        const crawl_movies_page = (pagesList) => {
            // Check for pause at page level
            if (isStopByUser) {
                return;
            }
            
            if (pagesList.length == 0) {
                showModal('success', "🎉 Crawling completed successfully!");
                hideSection(moviesListDiv);
                
                // Reset all buttons
                updateButtonStates(true);
                buttonSelectedCrawl.html("Collect Selected Pages");
                buttonUpdateCrawl.html("Collect Recent Videos");
                buttonFullCrawl.html("Collect All Videos");
                buttonRollCrawl.html("Randomize Order");
                
                // Reset data
                tempPageList = [];
                pageFromToList = [];
                tempHour = "";
                tempMoviesId = [];
                tempMovies = [];
                currentMovie = null;
                return;
            }
            
            let currentPage = pagesList.shift();
            tempPageList = pagesList;
            
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_get_movies_page",
                    api: apiUrl,
                    param: `pg=${currentPage}`,
                },
                beforeSend: function () {
                    // Check for pause before starting the request
                    if (isStopByUser) {
                        return;
                    }
                    
                    showSection(divCurrentPage);
                    $("#current-page-crawl h3").html(`📋 Crawling Page ${currentPage}`);
                    updateButtonStates(false);
                    buttonResumeCrawl.prop("disabled", true);
                    showSection(moviesListDiv);
                },
                success: function (response) {
                    // Check for pause after receiving response
                    if (isStopByUser) {
                        return;
                    }
                    
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        showModal('danger', `❌ ${data.message}`);
                    } else {
                        let avList = data.movies;
                        crawl_movie_by_id(avList, data.movies);
                    }
                },
                error: function() {
                    showModal('danger', "❌ Failed to fetch movies from page. Please try again.");
                }
            });
        };

        // Crawl movie by Id
        // Add a fourth argument: resumeMovie (default null)
        const crawl_movie_by_id = (avList, movies, showList = true, resumeMovie = null) => {
            // Check for pause at movie level
            if (isStopByUser) {
                return;
            }
            
            if (!avList || avList.length === 0) {
                // Clear the movies table and move to next page
                $("#movies-table tbody").html("");
                currentMovie = null;
                if (tempPageList && tempPageList.length > 0) {
                    crawl_movies_page(tempPageList, tempHour);
                } else {
                    // All pages completed
                    showModal('success', "🎉 Crawling completed successfully!");
                    hideSection(moviesListDiv);
                    
                    // Reset all buttons
                    updateButtonStates(true);
                    buttonSelectedCrawl.html("Collect Selected Pages");
                    buttonUpdateCrawl.html("Collect Recent Videos");
                    buttonFullCrawl.html("Collect All Videos");
                    buttonRollCrawl.html("Randomize Order");
                    
                    // Reset data
                    tempPageList = [];
                    pageFromToList = [];
                    tempHour = "";
                    tempMoviesId = [];
                    tempMovies = [];
                    currentMovie = null;
                }
                return;
            }
            
            // Only display movies if this is the first call for this page
            if (showList) {
                display_movies(movies);
            }
            
            let av;
            if (resumeMovie) {
                av = resumeMovie;
            } else {
                av = avList.shift();
            }
            tempMoviesId = avList;
            tempMovies = movies;
            currentMovie = av;
            
            if (av == null) {
                $("#movies-table tbody").html("");
                currentMovie = null;
                if (tempPageList && tempPageList.length > 0) {
                    crawl_movies_page(tempPageList, tempHour);
                }
                return;
            }
            
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_crawl_by_id",
                    crawl_image: $("#crawlImage").is(":checked") ? 1 : 0,
                    av: JSON.stringify(av)
                },
                beforeSend: function () {
                    // Check for pause before starting the request
                    if (isStopByUser) {
                        return;
                    }
                },
                success: function (response) {
                    // Check for pause after receiving response
                    if (isStopByUser) {
                        return;
                    }
                    
                    let data = JSON.parse(response);
                    if (data.code > 1) {
                        update_movies("movie-" + av.id, "❌ " + data.message);
                    } else {
                        update_movies("movie-" + av.id, "✅ " + data.message);
                    }
                    currentMovie = null; // Clear after processing
                    // Continue with next movie
                    crawl_movie_by_id(avList, movies, false);
                },
                error: function() {
                    update_movies("movie-" + av.id, "❌ Failed");
                    currentMovie = null; // Clear after processing
                    // Continue with next movie even on error
                    crawl_movie_by_id(avList, movies, false);
                }
            });
        };

        // Display movies list
        const display_movies = (movies) => {
            let trHTML = "";
            $.each(movies, function (idx, movie) {
                trHTML += `<tr id="movie-${movie.id}">
                    <td>${idx + 1}</td>
                    <td>${movie.name}</td>
                    <td><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...</td></tr>`;
            });
            $("#movies-table tbody").append(trHTML);
        };

        // Update movie crawling status
        const update_movies = (id, message = "100%") => {
            let doneIcon = `<svg style="stroke-width:2px;stroke:seagreen;" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="seagreen" class="bi bi-check-lg" viewBox="0 0 16 16">
                <path d="M12.736 3.97a.733.733 0 0 1 1.047 0c.286.289.29.756.01 1.05L7.88 12.01a.733.733 0 0 1-1.065.02L3.217 8.384a.757.757 0 0 1 0-1.06.733.733 0 0 1 1.047 0l3.052 3.093 5.4-6.425a.247.247 0 0 1 .02-.022Z"/>
                </svg>`;
            
            if (message.includes("✅")) {
                $("#" + id + " td:last-child").html(doneIcon + " " + message);
            } else if (message.includes("❌")) {
                $("#" + id + " td:last-child").html(`<span style="color: #dc3545;">❌ ${message.replace('❌ ', '')}</span>`);
            } else {
                $("#" + id + " td:last-child").html(message);
            }
        };
        // Expose to global scope for inline scripts
        window.display_movies = display_movies;
        window.crawl_movie_by_id = crawl_movie_by_id;

        // Cronjob settings variables
        let cronjobSettings = {};

        // Load cronjob settings on page load
        function loadCronjobSettings() {
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_get_cronjob_settings"
                },
                success: function(response) {
                    if (response.success) {
                        cronjobSettings = response.data;
                        updateCronjobUI();
                    }
                },
                error: function() {
                    console.log('Failed to load cronjob settings');
                }
            });
        }

        // Update cronjob UI with loaded settings
        function updateCronjobUI() {
            $('#cronjob-enabled-status').text(cronjobSettings.enabled ? 'Enabled' : 'Disabled');
            $('#cronjob-next-run').text(cronjobSettings.next_run_formatted || 'Disabled');
            $('#cronjob-last-run').text(cronjobSettings.last_run_formatted || 'Never');
            $('#cronjob-total-runs').text(cronjobSettings.total_runs || 0);
            $('#cronjob-last-status').text(cronjobSettings.last_status || 'No status available');

            // Update form fields
            $('#cronjob-enabled').prop('checked', cronjobSettings.enabled);
            $('#cronjob-api-url').val(cronjobSettings.api_url || 'https://avdbapi.com/api.php/provide/vod/?ac=detail');
            $(`input[name="crawling_method"][value="${cronjobSettings.crawling_method || 'recent'}"]`).prop('checked', true);
            $('#selected-pages-from').val(cronjobSettings.selected_pages_from || 1);
            $('#selected-pages-to').val(cronjobSettings.selected_pages_to || 5);
            $('#cronjob-schedule').val(cronjobSettings.schedule || 'twicedaily');
            $('#cronjob-download-images').prop('checked', cronjobSettings.download_images === true);
            $('#cronjob-force-update').prop('checked', cronjobSettings.force_update === true);

            // Show/hide settings based on enabled state
            if (cronjobSettings.enabled) {
                $('#cronjob-settings').show();
            } else {
                $('#cronjob-settings').hide();
            }

            // Show/hide selected pages range based on method
            if (cronjobSettings.crawling_method === 'selected') {
                $('#selected-pages-range').show();
            } else {
                $('#selected-pages-range').hide();
            }
        }

        // Initialize cronjob functionality
        loadCronjobSettings();

        // Cronjob enabled checkbox handler
        $('#cronjob-enabled').change(function() {
            if ($(this).is(':checked')) {
                $('#cronjob-settings').show();
            } else {
                $('#cronjob-settings').hide();
            }
        });

        // Crawling method radio button handler
        $('input[name="crawling_method"]').change(function() {
            if ($(this).val() === 'selected') {
                $('#selected-pages-range').show();
            } else {
                $('#selected-pages-range').hide();
            }
        });

        // Save cronjob settings
        $('#save-cronjob-settings').click(function() {
            const settings = {
                enabled: $('#cronjob-enabled').is(':checked'),
                api_url: $('#cronjob-api-url').val(),
                crawling_method: $('input[name="crawling_method"]:checked').val(),
                selected_pages_from: $('#selected-pages-from').val(),
                selected_pages_to: $('#selected-pages-to').val(),
                schedule: $('#cronjob-schedule').val(),
                download_images: $('#cronjob-download-images').is(':checked'),
                force_update: $('#cronjob-force-update').is(':checked')
            };

            setLoadingState($('#save-cronjob-settings'), true);
            $('#save-cronjob-settings').html('Saving...');

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_save_cronjob_settings",
                    ...settings
                },
                success: function(response) {
                    setLoadingState($('#save-cronjob-settings'), false);
                    $('#save-cronjob-settings').html('Save Settings');
                    
                    if (response.success) {
                        showModal('success', '✅ ' + response.data.message);
                        loadCronjobSettings(); // Reload settings to update UI
                    } else {
                        showError(response.data.message || 'Failed to save settings');
                    }
                },
                error: function() {
                    setLoadingState($('#save-cronjob-settings'), false);
                    $('#save-cronjob-settings').html('Save Settings');
                    showError("Failed to save cronjob settings. Please try again.");
                }
            });
        });

        // Test cronjob
        $('#test-cronjob').click(function() {
            setLoadingState($('#test-cronjob'), true);
            $('#test-cronjob').html('Testing...');

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_test_cronjob"
                },
                success: function(response) {
                    setLoadingState($('#test-cronjob'), false);
                    $('#test-cronjob').html('Test Now');
                    
                    if (response.success) {
                        showModal('success', '✅ ' + response.data.message);
                        loadCronjobSettings(); // Reload settings to update status
                    } else {
                        showError(response.data.message || 'Test failed');
                    }
                },
                error: function() {
                    setLoadingState($('#test-cronjob'), false);
                    $('#test-cronjob').html('Test Now');
                    showError("Failed to test cronjob. Please try again.");
                }
            });
        });

        // Stop cronjob
        $('#stop-cronjob').click(function() {
            if (!confirm('Are you sure you want to stop all scheduled cronjobs? This will immediately halt all auto-crawling.')) {
                return;
            }

            setLoadingState($('#stop-cronjob'), true);
            $('#stop-cronjob').html('Stopping...');

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_stop_cronjob"
                },
                success: function(response) {
                    setLoadingState($('#stop-cronjob'), false);
                    $('#stop-cronjob').html('Stop Now');
                    
                    if (response.success) {
                        showModal('success', '✅ ' + response.data.message);
                        loadCronjobSettings();
                    } else {
                        showError(response.data.message || 'Failed to stop cronjob');
                    }
                },
                error: function() {
                    setLoadingState($('#stop-cronjob'), false);
                    $('#stop-cronjob').html('Stop Now');
                    showError("Failed to stop cronjob. Please try again.");
                }
            });
        });

        // Clear cronjob lock
        $('#clear-cronjob-lock').click(function() {
            if (!confirm('Are you sure you want to clear all cronjob locks? This will allow new crawls to start if they were stuck.')) {
                return;
            }

            setLoadingState($('#clear-cronjob-lock'), true);
            $('#clear-cronjob-lock').html('Clearing...');

            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "avdbapi_clear_cronjob_lock"
                },
                success: function(response) {
                    setLoadingState($('#clear-cronjob-lock'), false);
                    $('#clear-cronjob-lock').html('Clear Lock');
                    
                    if (response.success) {
                        showModal('success', '✅ ' + response.data.message);
                        loadCronjobSettings();
                    } else {
                        showError(response.data.message || 'Failed to clear lock');
                    }
                },
                error: function() {
                    setLoadingState($('#clear-cronjob-lock'), false);
                    $('#clear-cronjob-lock').html('Clear Lock');
                    showError("Failed to clear lock. Please try again.");
                }
            });
        });
    });
})(jQuery);
