<?php
foreach (get_plugins() as $key => $value) {
    if ($value['Name'] == 'Adult API Crawler For Wp-Script') {
        $thisversion = $value['Version'];
    }
}

// Try to get the version from the plugin header if not set
if (!isset($thisversion) || !$thisversion) {
    // Try the current plugin directory first
    $plugin_file = plugin_dir_path(__DIR__) . '../avdbapi-crawler.php';
    if (file_exists($plugin_file)) {
        $plugin_data = get_file_data(
            $plugin_file,
            array('Version' => 'Version'),
            'plugin'
        );
        $thisversion = $plugin_data['Version'] ?? 'Unknown';
    } else {
        // Fallback to the expected directory
        $plugin_data = get_file_data(
            WP_PLUGIN_DIR . '/crawl-avdbapi/avdbapi-crawler.php',
            array('Version' => 'Version'),
            'plugin'
        );
        $thisversion = $plugin_data['Version'] ?? 'Unknown';
    }
}

$plugin_path = plugin_dir_url(__DIR__);
?>

<style>
.avdbapi-container {
    max-width: 1200px;
    margin: 0 auto;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.avdbapi-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 12px 12px 0 0;
    text-align: center;
    margin-bottom: 0;
}

.avdbapi-header h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.avdbapi-header .version {
    font-size: 1.1rem;
    opacity: 0.9;
    margin-top: 0.5rem;
}

.avdbapi-body {
    background: white;
    padding: 2rem;
    border-radius: 0 0 12px 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.instruction-card {
    background: #f8f9fa;
    border-left: 4px solid #007cba;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-radius: 8px;
}

.instruction-card h3 {
    color: #007cba;
    margin-top: 0;
    font-size: 1.3rem;
}

.instruction-card ul {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.instruction-card li {
    margin-bottom: 0.5rem;
    line-height: 1.6;
}

.api-section {
    background: #fff;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.api-section h3 {
    color: #495057;
    margin-top: 0;
    font-size: 1.4rem;
    margin-bottom: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #007cba;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #007cba;
    color: white;
}

.btn-primary:hover {
    background: #005a87;
    transform: translateY(-1px);
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #1e7e34;
    transform: translateY(-1px);
}

.btn-warning {
    background: #ffc107;
    color: #212529;
}

.btn-warning:hover {
    background: #e0a800;
    transform: translateY(-1px);
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
    transform: translateY(-1px);
}

.alert {
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    border: none;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border-left: 4px solid #28a745;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #17a2b8;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 1.5rem 0;
}

.stat-card {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #007cba;
    display: block;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.table th {
    background: #f8f9fa;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #e9ecef;
}

.table td {
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.table tr:hover {
    background: #f8f9fa;
}

.controls-section {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1.5rem;
    margin: 1.5rem 0;
}

.controls-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.control-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-check-input {
    width: 1.2rem;
    height: 1.2rem;
    margin: 0;
}

.form-check-label {
    margin: 0;
    font-weight: 500;
}

.progress-section {
    margin: 1.5rem 0;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.progress-controls {
    display: flex;
    gap: 0.5rem;
}

.hidden {
    display: none !important;
}

.spinner-border {
    width: 1rem;
    height: 1rem;
}

@media (max-width: 768px) {
    .avdbapi-header h1 {
        font-size: 2rem;
    }
    
    .controls-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="avdbapi-container">
    <!-- Header -->
    <div class="avdbapi-header">
        <h1 style="display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
            <span style="font-size: 2.2rem;">🎬</span> Adult API Crawler For Wp-Script
        </h1>
        <div class="version" style="font-size: 1.1rem; opacity: 0.9; margin-top: 0.5rem; color: #f3f3f3; letter-spacing: 1px;">
            Version <?php echo esc_html($thisversion); ?>
        </div>
    </div>

    <!-- Main Content -->
    <div class="avdbapi-body">
                <!-- Instructions -->
        <div class="instruction-card">
            <h3>📖 How to Use This Plugin</h3>
            <p>This plugin helps you automatically import adult video content from multiple API providers into your WordPress site. Follow these steps:</p>
            <ol>
                <li><strong>Enter your API URL</strong> - Use any supported API provider (AVDBAPI, XVIDAPI, etc.) or your custom endpoint</li>
                <li><strong>Test the connection</strong> - Click "Check API" to verify your API is working</li>
                <li><strong>Choose your crawling method</strong> - Select how many videos you want to import</li>
                <li><strong>Configure options</strong> - Leave image downloading disabled to save storage space</li>
                <li><strong>Start crawling</strong> - Click the appropriate collect button to begin</li>
            </ol>
        </div>

        <!-- API Configuration -->
        <div class="api-section">
            <h3>🔗 API Configuration</h3>
            <div class="form-group">
                <label for="jsonapi-url" class="form-label">API URL</label>
                <input type="text" class="form-control" id="jsonapi-url" 
                       value="https://avdbapi.com/api.php/provide/vod/?ac=detail"
                       placeholder="Enter your API URL">
            </div>
            
            <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
                <h5 style="margin: 0 0 0.5rem 0; color: #28a745;">🌐 Supported API Providers</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 0.5rem; font-size: 0.9rem;">
                    <div>
                        <strong>AVDBAPI:</strong><br>
                        <code>https://avdbapi.com/api.php/provide/vod/?ac=detail</code>
                    </div>
                    <div>
                        <strong>XVIDAPI:</strong><br>
                        <code>https://xvidapi.com/api.php/provide/vod/?ac=detail</code>
                    </div>
                    <div>
                        <strong>Custom API:</strong><br>
                        <code>https://yourdomain.com/api/endpoint</code>
                    </div>
                </div>
                <p style="margin: 0.5rem 0 0 0; font-size: 0.85rem; color: #6c757d;">
                    The plugin supports any API that returns JSON in the standard video format.
                </p>
            </div>
            
            <button class="btn btn-primary" type="button" id="api-check">
                <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                Check API Connection
            </button>
        </div>

        <!-- Multi-Link Import Section -->
        <div class="api-section" id="multi-link-section">
            <h3>📥 Multi-Link Import (Paste Single-Video API Links)</h3>
            <div class="form-group">
                <label for="multi-link-textarea" class="form-label">
                    Paste one or more single-video API links (one per line):
                    <br><small style="color:#666;">Example:<br>
                    https://avdbapi.com/api.php/provide/vod?ac=detail&ids=470318<br>
                    https://avdbapi.com/api.php/provide/vod?ac=detail&ids=470317<br>
                    (Paste each link on its own line. You can copy links from AVDBAPI search results or detail pages.)
                    </small>
                </label>
                <textarea id="multi-link-textarea" class="form-control" rows="4" placeholder="https://avdbapi.com/api.php/provide/vod?ac=detail&ids=470318
https://avdbapi.com/api.php/provide/vod?ac=detail&ids=470317
..."></textarea>
            </div>
            <button class="btn btn-success" type="button" id="multi-link-import-btn">
                <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                Import Videos
            </button>
            <div id="multi-link-result" style="margin-top:1rem;"></div>
        </div>

        <!-- Alert Box -->
        <div id="alert-box" class="alert hidden" role="alert"></div>

        <!-- Page Range Configuration -->
        <div id="content" class="api-section hidden">
            <h3>📄 Page Range Configuration</h3>
            <p>Specify which pages of videos you want to import:</p>
            <div class="form-group">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label for="page-from" class="form-label">From Page</label>
                        <input type="number" class="form-control" id="page-from" name="page-from" 
                               placeholder="e.g., 1" min="1">
                    </div>
                    <div>
                        <label for="page-to" class="form-label">To Page</label>
                        <input type="number" class="form-control" id="page-to" name="page-to" 
                               placeholder="e.g., 10" min="1">
                    </div>
                </div>
            </div>
            <button class="btn btn-primary" type="button" id="page-from-to">Set Page Range</button>
        </div>

        <!-- Statistics -->
        <div id="stats-section" class="api-section hidden">
            <h3>📊 API Statistics</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <span class="stat-number" id="last-page">-</span>
                    <div class="stat-label">Total Pages</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="per-page">-</span>
                    <div class="stat-label">Videos Per Page</div>
                </div>
                <div class="stat-card">
                    <span class="stat-number" id="movies-total">-</span>
                    <div class="stat-label">Total Videos</div>
                </div>
            </div>
        </div>

        <!-- Crawling Controls -->
        <div id="crawling-section" class="api-section hidden">
            <h3>⚙️ Crawling Options</h3>
            
                         <div class="controls-section">
                 <h4>Options</h4>
                 <div class="controls-grid">
                     <div class="control-group">
                         <input class="form-check-input" type="checkbox" id="crawlImage">
                         <label class="form-check-label" for="crawlImage">
                             Download and set video thumbnails
                         </label>
                     </div>
                 </div>
                 
                 <div style="margin-top: 1rem; padding: 1rem; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #007cba;">
                     <h5 style="margin: 0 0 0.5rem 0; color: #007cba;">💡 Storage Optimization Tip</h5>
                     <p style="margin: 0; font-size: 0.9rem; color: #495057; line-height: 1.5;">
                         <strong>Recommended:</strong> Leave this option <strong>unchecked</strong> to save storage space. 
                         The plugin will use thumbnail URLs directly from the API instead of downloading images to your server. 
                         This approach:
                     </p>
                     <ul style="margin: 0.5rem 0 0 0; padding-left: 1.5rem; font-size: 0.9rem; color: #495057;">
                         <li>✅ Saves significant server storage space</li>
                         <li>✅ Speeds up crawling process</li>
                         <li>✅ Reduces bandwidth usage</li>
                         <li>✅ Prevents duplicate image downloads</li>
                         <li>✅ Works with most video themes</li>
                     </ul>
                     <p style="margin: 0.5rem 0 0 0; font-size: 0.9rem; color: #6c757d;">
                         <strong>Only enable</strong> if your theme specifically requires local image files or if you need offline access to thumbnails.
                     </p>
                 </div>
             </div>

            <div class="controls-section">
                <h4>Crawling Methods</h4>
                <div class="controls-grid">
                    <button id="selected-crawl" class="btn btn-warning" disabled>
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Collect Selected Pages
                    </button>
                    <button id="update-crawl" class="btn btn-warning" disabled>
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Collect Recent Videos
                    </button>
                    <button id="full-crawl" class="btn btn-primary" disabled>
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Collect All Videos
                    </button>
                </div>
                <div style="margin-top: 1rem;">
                    <button id="roll-crawl" class="btn btn-success" disabled>
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Randomize Order
                    </button>
                </div>
            </div>
        </div>

        <!-- Progress Section -->
        <div id="movies-list" class="api-section hidden">
            <div class="progress-section">
                <div class="progress-header">
                    <h3 id="current-page-crawl">📋 Crawling Progress</h3>
                    <div class="progress-controls">
                        <button id="pause-crawl" class="btn btn-warning" disabled>
                            ⏸️ Pause
                        </button>
                        <button id="resume-crawl" class="btn btn-success" disabled>
                            ▶️ Resume
                        </button>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table" id="movies-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Video Title</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cronjob Settings Section -->
        <div class="api-section">
            <h3>⏰ Auto-Crawling Schedule (Cronjob)</h3>
            <div style="margin-bottom: 1.5rem; padding: 1rem; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
                <h5 style="margin: 0 0 0.5rem 0; color: #856404;">📋 What is Auto-Crawling?</h5>
                <p style="margin: 0; font-size: 0.9rem; color: #856404; line-height: 1.5;">
                    <strong>Auto-Crawling</strong> allows the plugin to automatically import videos from your API at scheduled intervals. 
                    This is perfect for keeping your site updated with fresh content without manual intervention.
                </p>
            </div>

            <!-- Cronjob Status -->
            <div id="cronjob-status" class="alert alert-info" style="margin-bottom: 1.5rem;">
                <h5 style="margin: 0 0 0.5rem 0;">📊 Cronjob Status</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; font-size: 0.9rem;">
                    <div>
                        <strong>Status:</strong> <span id="cronjob-enabled-status">Loading...</span>
                    </div>
                    <div>
                        <strong>Next Run:</strong> <span id="cronjob-next-run">Loading...</span>
                    </div>
                    <div>
                        <strong>Last Run:</strong> <span id="cronjob-last-run">Loading...</span>
                    </div>
                    <div>
                        <strong>Total Runs:</strong> <span id="cronjob-total-runs">Loading...</span>
                    </div>
                </div>
                <div style="margin-top: 0.5rem;">
                    <strong>Last Status:</strong> <span id="cronjob-last-status">Loading...</span>
                </div>
            </div>

            <!-- Cronjob Configuration -->
            <div class="form-group">
                <div class="control-group" style="margin-bottom: 1rem;">
                    <input class="form-check-input" type="checkbox" id="cronjob-enabled">
                    <label class="form-check-label" for="cronjob-enabled" style="font-weight: 600; font-size: 1.1rem;">
                        Enable Auto-Crawling
                    </label>
                </div>
            </div>

            <div id="cronjob-settings" style="display: none;">
                <!-- API URL for Cronjob -->
                <div class="form-group">
                    <label for="cronjob-api-url" class="form-label">API URL for Auto-Crawling</label>
                    <input type="text" class="form-control" id="cronjob-api-url" 
                           value="https://avdbapi.com/api.php/provide/vod/?ac=detail"
                           placeholder="Enter your API URL">
                    <small class="form-text text-muted">
                        This can be different from the manual API URL above. Supports AVDBAPI, XVIDAPI, or custom APIs.
                    </small>
                </div>

                <!-- Crawling Method -->
                <div class="form-group">
                    <label class="form-label">Crawling Method</label>
                    <div class="controls-grid" style="grid-template-columns: 1fr;">
                        <div class="control-group">
                            <input class="form-check-input" type="radio" name="crawling_method" id="method-recent" value="recent" checked>
                            <label class="form-check-label" for="method-recent">
                                <strong>Collect Recent Videos</strong> - Import videos from the first 5 pages (recommended for regular updates)
                            </label>
                        </div>
                        <div class="control-group">
                            <input class="form-check-input" type="radio" name="crawling_method" id="method-selected" value="selected">
                            <label class="form-check-label" for="method-selected">
                                <strong>Collect Selected Pages</strong> - Import videos from specific page range
                            </label>
                        </div>
                        <div class="control-group">
                            <input class="form-check-input" type="radio" name="crawling_method" id="method-all" value="all">
                            <label class="form-check-label" for="method-all">
                                <strong>Collect All Videos</strong> - Import videos from all available pages (use with caution)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Selected Pages Range (shown only when "selected" is chosen) -->
                <div id="selected-pages-range" class="form-group" style="display: none;">
                    <label class="form-label">Page Range</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div>
                            <label for="selected-pages-from" class="form-label">From Page</label>
                            <input type="number" class="form-control" id="selected-pages-from" value="1" min="1">
                        </div>
                        <div>
                            <label for="selected-pages-to" class="form-label">To Page</label>
                            <input type="number" class="form-control" id="selected-pages-to" value="5" min="1">
                        </div>
                    </div>
                    <small class="form-text text-muted">
                        Specify which pages to crawl. For example, pages 1-5 will import videos from the first 5 pages.
                    </small>
                </div>

                <!-- Schedule -->
                <div class="form-group">
                    <label for="cronjob-schedule" class="form-label">Schedule</label>
                    <select class="form-control" id="cronjob-schedule">
                        <option value="every_30_minutes">Every 30 Minutes</option>
                        <option value="hourly">Every Hour</option>
                        <option value="every_2_hours">Every 2 Hours</option>
                        <option value="every_6_hours">Every 6 Hours</option>
                        <option value="twicedaily" selected>Twice Daily</option>
                        <option value="daily">Once Daily</option>
                    </select>
                    <small class="form-text text-muted">
                        Choose how often the auto-crawling should run. More frequent runs = more server load.
                    </small>
                </div>

                <!-- Options -->
                <div class="form-group">
                    <div class="control-group">
                        <input class="form-check-input" type="checkbox" id="cronjob-download-images">
                        <label class="form-check-label" for="cronjob-download-images">
                            Download and set video thumbnails (not recommended - uses more storage)
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="control-group">
                        <input class="form-check-input" type="checkbox" id="cronjob-force-update">
                        <label class="form-check-label" for="cronjob-force-update">
                            Force update existing videos (otherwise skip duplicates)
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                    <button class="btn btn-success" type="button" id="save-cronjob-settings">
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Save Settings
                    </button>
                    <button class="btn btn-warning" type="button" id="test-cronjob">
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Test Now
                    </button>
                    <button class="btn btn-danger" type="button" id="stop-cronjob" title="Immediately stop all scheduled cronjobs. Useful if you want to halt all auto-crawling right away.">
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Stop Now
                    </button>
                    <button class="btn btn-secondary" type="button" id="clear-cronjob-lock" title="Clear stuck locks. Use this if you see 'Another crawl is already running' but no crawl is actually running.">
                        <span class="spinner-border spinner-border-sm hidden" role="status"></span>
                        Clear Lock
                    </button>
                </div>
            </div>

            <!-- Cronjob Documentation -->
            <div style="margin-top: 2rem; padding: 1.5rem; background: #e7f3ff; border-radius: 8px; border-left: 4px solid #007cba;">
                <h5 style="margin: 0 0 1rem 0; color: #007cba;">📖 Auto-Crawling Guide</h5>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                    <div>
                        <h6 style="margin: 0 0 0.5rem 0; color: #495057;">🎯 Recommended Setup</h6>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: #495057;">
                            <li>Use "Collect Recent Videos" for regular updates</li>
                            <li>Set schedule to "Twice Daily" for fresh content</li>
                            <li>Leave image downloading disabled to save storage</li>
                            <li>Test the cronjob before enabling</li>
                        </ul>
                    </div>
                    <div>
                        <h6 style="margin: 0 0 0.5rem 0; color: #495057;">⚠️ Important Notes</h6>
                        <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: #495057;">
                            <li>Auto-crawling runs in the background</li>
                            <li>Videos are automatically updated if they already exist</li>
                            <li>Server load depends on schedule frequency</li>
                            <li>Check "Last Status" for recent activity</li>
                        </ul>
                    </div>
                </div>
                <div style="margin-top: 1rem; padding: 1rem; background: #fff; border-radius: 6px;">
                    <h6 style="margin: 0 0 0.5rem 0; color: #495057;">🔧 Troubleshooting</h6>
                    <p style="margin: 0; font-size: 0.9rem; color: #495057;">
                        <strong>If auto-crawling isn't working:</strong> WordPress cronjobs require site traffic to trigger. 
                        For more reliable scheduling, consider setting up a real cronjob or using a service like WP-Cron Control.
                    </p>
                </div>
            </div>
        </div>

        <!-- Help Section -->
        <div class="instruction-card">
            <h3>💡 Tips & Help</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
                                                  <div>
                     <h4>🎯 Recommended Workflow</h4>
                     <ul>
                         <li>Start with "Collect Recent Videos" for testing</li>
                         <li>Use "Collect Selected Pages" for specific ranges</li>
                         <li>Leave image downloading disabled to save storage</li>
                         <li>Use "Randomize Order" to avoid API rate limits</li>
                         <li>Test with different API providers for best results</li>
                     </ul>
                 </div>
                 <div>
                     <h4>⚠️ Important Notes</h4>
                     <ul>
                         <li>Videos are automatically updated if they already exist</li>
                         <li>Existing comments and engagement data are preserved</li>
                         <li>Thumbnail URLs are used by default (saves storage)</li>
                         <li>You can pause and resume crawling at any time</li>
                         <li>Local image downloads are optional and not recommended</li>
                         <li>Works with any API provider using standard JSON format</li>
                     </ul>
                 </div>
            </div>
        </div>
    </div>
</div>

<!-- Global Message Modal (Bootstrap) -->
<div class="modal fade" id="globalMessageModal" tabindex="-1" aria-labelledby="globalMessageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="globalMessageModalLabel">Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="globalMessageModalBody">
        <!-- Message will be inserted here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Add some helpful tooltips and better UX
document.addEventListener('DOMContentLoaded', function() {
    // Add helpful hints
    const apiInput = document.getElementById('jsonapi-url');
    apiInput.addEventListener('focus', function() {
        this.style.borderColor = '#007cba';
    });
    
    apiInput.addEventListener('blur', function() {
        this.style.borderColor = '#e9ecef';
    });
    
    // Add loading states
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            const spinner = this.querySelector('.spinner-border');
            if (spinner) {
                spinner.classList.remove('hidden');
            }
        });
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const importBtn = document.getElementById('multi-link-import-btn');
    const textarea = document.getElementById('multi-link-textarea');
    const resultDiv = document.getElementById('multi-link-result');

    importBtn.addEventListener('click', function() {
        resultDiv.innerHTML = '';
        const spinner = importBtn.querySelector('.spinner-border');
        if (spinner) spinner.classList.remove('hidden');
        importBtn.disabled = true;

        // Extract all IDs from textarea
        let text = textarea.value;
        let ids = [];
        // Match all numbers after 'ids=' or just numbers
        let regex = /ids=(\d+)/g;
        let match;
        while ((match = regex.exec(text)) !== null) {
            ids.push(match[1]);
        }
        // Also allow direct numbers (comma, space, or newline separated)
        if (ids.length === 0) {
            ids = text.split(/[^\d]+/).filter(Boolean);
        }
        if (ids.length === 0) {
            resultDiv.innerHTML = '<div class="alert alert-danger">No valid video IDs found!</div>';
            if (spinner) spinner.classList.add('hidden');
            importBtn.disabled = false;
            return;
        }

        // Send AJAX request to backend
        const formData = new FormData();
        formData.append('action', 'avdbapi_multi_link_import');
        formData.append('ids', ids.join(','));

        fetch(ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                resultDiv.innerHTML = '<div class="alert alert-success">' + (data.data && data.data.message ? data.data.message : 'Success!') + '</div>';
                // If movies are returned, display and crawl them
                if (data.data && data.data.movies && Array.isArray(data.data.movies) && data.data.movies.length > 0) {
                    const moviesListDiv = document.getElementById('movies-list');
                    if (moviesListDiv) moviesListDiv.classList.remove('hidden');
                    // Clear previous rows before displaying new ones
                    document.querySelector("#movies-table tbody").innerHTML = "";
                    crawl_movie_by_id([...data.data.movies], data.data.movies);
                }
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">' + (data.data && data.data.message ? data.data.message : 'Import failed!') + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
        })
        .finally(() => {
            if (spinner) spinner.classList.add('hidden');
            importBtn.disabled = false;
        });
    });
});
</script>