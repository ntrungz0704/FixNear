/**
 * FixNear Unified Location Manager (FixNearLocation)
 * Quản lý tập trung trạng thái vị trí (GPS/Quận/Khu vực) trên toàn hệ thống.
 * Đồng bộ hai chiều: Browser localStorage <-> PHP Session ($_SESSION['fixnear_location']).
 * Bật 1 lần duy nhất, ghi nhớ xuyên suốt tất cả các trang, không hỏi lại phiền toái.
 */

(function (window) {
    'use strict';

    const STORAGE_KEY = 'fixnear_location';
    const DISMISSED_KEY = 'fixnear_location_prompt_dismissed';

    const FixNearLocation = {
        _state: null,
        _isInitialized: false,

        /**
         * Khởi tạo Location Manager
         */
        init: function () {
            if (this._isInitialized) return;
            this._isInitialized = true;

            // 1. Đọc vị trí từ localStorage hoặc migrate từ key cũ
            this._loadFromStorage();

            // 2. Kiểm tra nếu URL có chứa query param GPS cũ thì nạp và làm sạch URL
            this._ingestAndCleanUrl();

            // 3. Nếu browser đã có vị trí nhưng server chưa đồng bộ, gửi background sync
            this._syncWithServerIfNeeded();

            // 4. Cập nhật các thành phần giao diện hiển thị vị trí trên trang
            this.updateUI();

            // 5. Lắng nghe thay đổi từ các tab khác (storage event)
            window.addEventListener('storage', (event) => {
                if (event.key === STORAGE_KEY) {
                    this._loadFromStorage();
                    this.updateUI();
                    this._dispatchChangeEvent();
                }
            });
        },

        /**
         * Lấy thông tin vị trí hiện tại
         * @returns {Object|null} {lat, lng, name, accuracy, timestamp, source, status}
         */
        get: function () {
            if (!this._state) {
                this._loadFromStorage();
            }
            return this._state;
        },

        /**
         * Kiểm tra đã có vị trí hợp lệ hay chưa
         * @returns {boolean}
         */
        hasLocation: function () {
            const loc = this.get();
            return !!(loc && typeof loc.lat === 'number' && typeof loc.lng === 'number' && !isNaN(loc.lat) && !isNaN(loc.lng));
        },

        /**
         * Lưu vị trí mới vào State, LocalStorage, Cookies và gửi sync lên PHP Session
         * @param {Object} locData
         * @param {boolean} [andSync=true]
         */
        save: function (locData, andSync = true) {
            if (!locData || typeof locData.lat !== 'number' || typeof locData.lng !== 'number') {
                console.error('[FixNearLocation] Tọa độ không hợp lệ:', locData);
                return false;
            }

            const cleanName = String(locData.name || 'Vị trí GPS của bạn').trim() || 'Vị trí GPS của bạn';
            const locationState = {
                lat: Number(locData.lat),
                lng: Number(locData.lng),
                name: cleanName,
                accuracy: typeof locData.accuracy === 'number' ? locData.accuracy : null,
                timestamp: Math.floor(Date.now() / 1000),
                source: locData.source || 'gps',
                status: 'granted'
            };

            this._state = locationState;

            // Lưu LocalStorage chuẩn mới
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(locationState));
                // Hỗ trợ tương thích ngược cho các đoạn mã cũ
                localStorage.setItem('fixnear_user_lat', String(locationState.lat));
                localStorage.setItem('fixnear_user_lng', String(locationState.lng));
                localStorage.setItem('fixnear_loc_name', locationState.name);
                sessionStorage.setItem('fixnear_session_located', '1');
            } catch (e) {
                console.warn('[FixNearLocation] Không thể ghi localStorage:', e);
            }

            // Ghi cookie 30 ngày để PHP backend đọc ngay khi reload/đổi trang
            const maxAge = 86400 * 30;
            document.cookie = `fixnear_lat=${locationState.lat}; path=/; max-age=${maxAge}; SameSite=Lax`;
            document.cookie = `fixnear_lng=${locationState.lng}; path=/; max-age=${maxAge}; SameSite=Lax`;
            document.cookie = `fixnear_loc=${encodeURIComponent(locationState.name)}; path=/; max-age=${maxAge}; SameSite=Lax`;

            // Đồng bộ lên PHP Session bằng Fetch API
            if (andSync) {
                this.syncToServer(locationState);
            }

            // Cập nhật giao diện & thông báo sự kiện
            this.updateUI();
            this._dispatchChangeEvent();
            return true;
        },

        /**
         * Xóa vị trí (người dùng tắt GPS hoặc reset khu vực)
         * @param {boolean} [andSync=true]
         */
        clear: function (andSync = true) {
            this._state = null;

            try {
                localStorage.removeItem(STORAGE_KEY);
                localStorage.removeItem('fixnear_user_lat');
                localStorage.removeItem('fixnear_user_lng');
                localStorage.removeItem('fixnear_loc_name');
                sessionStorage.removeItem('fixnear_session_located');
            } catch (e) {}

            // Xóa cookies
            document.cookie = 'fixnear_lat=; path=/; max-age=0; SameSite=Lax';
            document.cookie = 'fixnear_lng=; path=/; max-age=0; SameSite=Lax';
            document.cookie = 'fixnear_loc=; path=/; max-age=0; SameSite=Lax';

            // Đồng bộ xóa lên PHP Session
            if (andSync) {
                this.syncClearToServer();
            }

            this.updateUI();
            this._dispatchChangeEvent();
        },

        /**
         * Đặt vị trí theo Quận / Khu vực chọn thủ công
         * @param {number} lat
         * @param {number} lng
         * @param {string} name
         * @param {boolean} [reloadPage=false]
         */
        setDistrict: function (lat, lng, name, reloadPage = false) {
            this.save({
                lat: Number(lat),
                lng: Number(lng),
                name: name,
                source: 'district',
                status: 'granted'
            }, true);

            // Đóng modal chọn vị trí nếu đang mở
            const locModal = document.getElementById('fn-location-modal');
            if (locModal) {
                locModal.classList.remove('active');
                document.body.style.overflow = '';
            }

            if (reloadPage) {
                // Tự động làm sạch URL trước khi reload
                this.cleanUrlGPS();
                window.location.reload();
            }
        },

        /**
         * Yêu cầu quyền truy cập GPS từ trình duyệt
         * @param {Function} [onSuccess] Callback khi lấy tọa độ thành công
         * @param {Function} [onError] Callback khi bị từ chối hoặc lỗi
         * @param {Object} [options]
         */
        requestGPS: function (onSuccess, onError, options = {}) {
            if (!navigator.geolocation) {
                if (typeof onError === 'function') {
                    onError(new Error('Trình duyệt không hỗ trợ Geolocation'));
                }
                return;
            }

            const geoOptions = Object.assign({
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 60000
            }, options);

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const lat = pos.coords.latitude;
                    const lng = pos.coords.longitude;
                    const accuracy = pos.coords.accuracy;

                    const locData = {
                        lat: lat,
                        lng: lng,
                        name: 'Vị trí GPS của bạn',
                        accuracy: accuracy,
                        source: 'gps',
                        status: 'granted'
                    };

                    this.save(locData, true);

                    // Đóng modal nếu đang mở
                    const locModal = document.getElementById('fn-location-modal');
                    if (locModal) {
                        locModal.classList.remove('active');
                        document.body.style.overflow = '';
                    }

                    if (typeof onSuccess === 'function') {
                        onSuccess(locData);
                    }
                },
                (err) => {
                    console.warn('[FixNearLocation] Geolocation error:', err.code, err.message);
                    if (err.code === 1) { // PERMISSION_DENIED
                        try {
                            localStorage.setItem(DISMISSED_KEY, 'denied');
                        } catch (e) {}
                    }
                    if (typeof onError === 'function') {
                        onError(err);
                    }
                },
                geoOptions
            );
        },

        /**
         * Gửi vị trí lên API sync_location.php để lưu vào PHP Session
         * @param {Object} loc
         */
        syncToServer: function (loc) {
            if (!loc) return;
            const prefix = this._getAssetPrefix();
            const endpoint = prefix + 'api/sync_location.php';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    lat: loc.lat,
                    lng: loc.lng,
                    name: loc.name,
                    accuracy: loc.accuracy,
                    source: loc.source || 'gps'
                })
            }).catch(err => {
                console.warn('[FixNearLocation] Không thể đồng bộ vị trí lên server:', err);
            });
        },

        /**
         * Gửi thông báo xóa vị trí lên server
         */
        syncClearToServer: function () {
            const prefix = this._getAssetPrefix();
            const endpoint = prefix + 'api/sync_location.php';

            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: 'clear' })
            }).catch(err => {
                console.warn('[FixNearLocation] Không thể xóa vị trí trên server:', err);
            });
        },

        /**
         * Xóa các tham số GPS thừa khỏi URL hiện tại bằng history.replaceState
         * Giúp URL sạch đẹp, không gây hiểu lầm hoặc stale param khi bookmark/share
         */
        cleanUrlGPS: function () {
            try {
                const currentUrl = new URL(window.location.href);
                let changed = false;

                ['user_lat', 'user_lng', 'loc_name'].forEach(param => {
                    if (currentUrl.searchParams.has(param)) {
                        currentUrl.searchParams.delete(param);
                        changed = true;
                    }
                });

                if (changed) {
                    const cleanPath = currentUrl.pathname + (currentUrl.search ? currentUrl.search : '') + currentUrl.hash;
                    window.history.replaceState({}, document.title, cleanPath);
                }
            } catch (e) {
                console.warn('[FixNearLocation] cleanUrlGPS error:', e);
            }
        },

        /**
         * Cập nhật các badge và nút hiển thị vị trí trên giao diện
         */
        updateUI: function () {
            const loc = this.get();
            const isLocated = this.hasLocation();

            // Cập nhật text vị trí
            document.querySelectorAll('.fn-user-location-name, [data-fn-location-text]').forEach(el => {
                el.textContent = isLocated ? loc.name : 'Chưa xác định vị trí';
            });

            // Cập nhật badge GPS Hero
            const heroStatus = document.getElementById('fn-gps-status-text');
            if (heroStatus) {
                heroStatus.textContent = isLocated ? `📍 ${loc.name}` : '📍 Chưa bật GPS';
            }

            const gpsToggleBtn = document.getElementById('fn-gps-toggle-btn');
            if (gpsToggleBtn) {
                if (isLocated) {
                    gpsToggleBtn.style.background = '#dcfce7';
                    gpsToggleBtn.style.borderColor = '#16a34a';
                    gpsToggleBtn.style.color = '#16a34a';
                    gpsToggleBtn.title = 'Bấm để tắt hoặc đổi vị trí';
                    const subText = gpsToggleBtn.querySelector('span:last-child');
                    if (subText) subText.textContent = '[Tắt ✕]';
                } else {
                    gpsToggleBtn.style.background = '#f3f4f6';
                    gpsToggleBtn.style.borderColor = '#d1d5db';
                    gpsToggleBtn.style.color = '#6b7280';
                    gpsToggleBtn.title = 'Bấm để bật GPS tự động';
                    const subText = gpsToggleBtn.querySelector('span:last-child');
                    if (subText) subText.textContent = '[Bật ⚙️]';
                }
            }
        },

        // --- INTERNAL HELPERS ---

        _loadFromStorage: function () {
            try {
                const stored = localStorage.getItem(STORAGE_KEY);
                if (stored) {
                    const parsed = JSON.parse(stored);
                    if (parsed && typeof parsed.lat === 'number' && typeof parsed.lng === 'number') {
                        this._state = parsed;
                        return;
                    }
                }

                // Fallback: Kiểm tra key cũ
                const oldLat = localStorage.getItem('fixnear_user_lat');
                const oldLng = localStorage.getItem('fixnear_user_lng');
                const oldName = localStorage.getItem('fixnear_loc_name');
                if (oldLat && oldLng && !isNaN(Number(oldLat)) && !isNaN(Number(oldLng))) {
                    this._state = {
                        lat: Number(oldLat),
                        lng: Number(oldLng),
                        name: oldName || 'Vị trí đã lưu',
                        accuracy: null,
                        timestamp: Math.floor(Date.now() / 1000),
                        source: 'localStorage_migration',
                        status: 'granted'
                    };
                    localStorage.setItem(STORAGE_KEY, JSON.stringify(this._state));
                }
            } catch (e) {
                this._state = null;
            }
        },

        _ingestAndCleanUrl: function () {
            try {
                const currentUrl = new URL(window.location.href);
                const urlLat = currentUrl.searchParams.get('user_lat');
                const urlLng = currentUrl.searchParams.get('user_lng');
                const urlName = currentUrl.searchParams.get('loc_name');

                if (urlLat && urlLng && !isNaN(Number(urlLat)) && !isNaN(Number(urlLng))) {
                    this.save({
                        lat: Number(urlLat),
                        lng: Number(urlLng),
                        name: urlName || 'Vị trí GPS của bạn',
                        source: 'url',
                        status: 'granted'
                    }, true);
                }

                // Tự động làm sạch URL loại bỏ query GPS
                this.cleanUrlGPS();
            } catch (e) {}
        },

        _syncWithServerIfNeeded: function () {
            // Nếu có state nhưng chưa từng gửi sync trong phiên này
            if (this.hasLocation() && !sessionStorage.getItem('fixnear_synced_session')) {
                this.syncToServer(this._state);
                sessionStorage.setItem('fixnear_synced_session', '1');
            }
        },

        _getAssetPrefix: function () {
            return window.location.pathname.includes('/admin/') ? '../' : '';
        },

        _dispatchChangeEvent: function () {
            try {
                const event = new CustomEvent('fixnear:location-updated', {
                    detail: this._state
                });
                window.dispatchEvent(event);
            } catch (e) {}
        }
    };

    // Xuất ra global scope
    window.FixNearLocation = FixNearLocation;

    // Tự động khởi chạy ngay khi tải file script
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => FixNearLocation.init());
    } else {
        FixNearLocation.init();
    }

})(window);
