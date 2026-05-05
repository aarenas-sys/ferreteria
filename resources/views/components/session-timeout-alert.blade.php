<div class="fixed inset-0 flex items-center justify-center z-40 pointer-events-none" 
     x-data="initSessionTimeout()"
     @mounted="init()"
     x-cloak>
    
    <!-- Alerta de sesión por expirar - Centrada en la pantalla -->
    <div x-show="showWarning" 
         x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         :class="showWarning ? 'z-[9999] pointer-events-auto' : 'z-40 pointer-events-none'"
         class="bg-gray-100 dark:bg-gray-900 border-l-8 border-red-600 dark:border-red-400 p-6 rounded-lg shadow-2xl max-w-md backdrop-blur-sm">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-8 w-8 text-red-600 dark:text-red-300 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-4 flex-1">
                <h3 class="text-lg font-bold text-gray-400 dark:text-gray-100">
                    ⏱️ ¡SESIÓN POR EXPIRAR!
                </h3>
                <p class="mt-2 text-sm text-gray-400 dark:text-gray-100">
                    Tu sesión expirará por inactividad en <span class="ml-1 font-bold text-lg text-red-700 dark:text-red-100" x-text="formatTime(timeRemaining)"></span>
                </p>
                <div class="mt-4">
                    <div class="w-full bg-red-200 dark:bg-red-700 rounded-full h-3">
                        <div class="bg-gradient-to-r from-red-600 to-red-500 dark:from-red-400 dark:to-red-300 h-3 rounded-full transition-all duration-300" 
                             :style="`width: ${(timeRemaining / totalTimeout) * 100}%`"></div>
                    </div>
                </div>
                <div class="mt-5 flex gap-3">
                    <button @click.stop="continueSession()" 
                            class="inline-flex items-center px-4 py-2 bg-red-600 dark:bg-red-700 hover:bg-red-700 dark:hover:bg-red-600 text-white font-bold rounded-lg transition-colors shadow-lg hover:shadow-xl">
                        Continuar Sesión
                    </button>
                    <button @click.stop="logout()" 
                            class="inline-flex items-center px-4 py-2 bg-gray-400 dark:bg-gray-600 hover:bg-gray-500 dark:hover:bg-gray-700 text-gray-900 dark:text-gray-100 font-semibold rounded-lg transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function initSessionTimeout() {
    return {
        showWarning: false,
        inactivityTimer: null,
        pollingTimer: null,
        pageVisibilityTimer: null,
        timeRemaining: 0,
        totalTimeout: 120,
        warningThreshold: 30,
        inactivitySeconds: 0,
        isInitialized: false,
        wasInactive: false,
        
        init() {
            // Evitar inicialización múltiple
            if (this.isInitialized) return;
            this.isInitialized = true;
            
            console.log('🟢 Session timeout monitoring initialized');
            
            // Iniciar tracking con pequeño delay para evitar flashes
            setTimeout(() => {
                this.startInactivityTracking();
                this.startPolling();
                this.initPageVisibilityDetection();
            }, 500);
            
            // Detectar actividad en el documento (solo si NO hay alerta)
            document.addEventListener('mousemove', () => {
                if (!this.showWarning) {
                    this.resetInactivityTimer();
                }
            });
            document.addEventListener('keydown', () => {
                if (!this.showWarning) {
                    this.resetInactivityTimer();
                }
            });
            document.addEventListener('click', () => {
                if (!this.showWarning) {
                    this.resetInactivityTimer();
                }
            });
        },

        /**
         * Detecta cuando el usuario cambia de pestaña o minimiza el navegador
         * Valida la sesión al regresar
         */
        initPageVisibilityDetection() {
            const self = this; // Guardar referencia correcta a 'this'
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    console.log('👋 Page hidden - user left tab or minimized browser');
                    self.wasInactive = true;
                } else {
                    console.log('👀 Page visible again - user returned to tab');
                    // Si el usuario estuvo inactivo, verificar que la sesión siga válida
                    if (self.wasInactive) {
                        console.log('🔄 Validating session after tab return...');
                        self.validateSessionAfterTabReturn();
                        self.wasInactive = false;
                    }
                }
            });
        },

        /**
         * Valida la sesión cuando el usuario regresa a la pestaña
         * Si la sesión expiró en el servidor, hace logout automático
         */
        validateSessionAfterTabReturn() {
            const self = this; // Guardar referencia correcta a 'this'
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            console.log('🔍 Fetching /session/ping to validate session...');
            
            fetch('/session/ping', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                console.log('📊 Response status:', response.status);
                if (response.status === 401 || response.status === 419) {
                    console.log('🔴 Session expired on server (401/419) - performing logout');
                    self.autoLogout();
                    return null;
                } else if (response.ok) {
                    console.log('✅ Session still valid after tab return - resetting timers');
                    self.resetInactivityTimer();
                    return response.json();
                } else {
                    console.warn('⚠️ Unexpected response validating session:', response.status);
                    return response.json().catch(() => null);
                }
            })
            .catch(error => {
                console.error('❌ Error validating session after tab return:', error);
                // No hacer logout si hay error de red - podría ser temporal
            });
        },

        startInactivityTracking() {
            const self = this;
            this.inactivityTimer = setInterval(() => {
                self.inactivitySeconds += 1;
                
                if (self.inactivitySeconds % 10 === 0) {
                    console.log(`⏱️ Inactivity: ${self.inactivitySeconds}s`);
                }
                
                if (self.inactivitySeconds >= (self.totalTimeout - self.warningThreshold)) {
                    if (!self.showWarning) {
                        console.log('⚠️ Session warning - showing alert');
                        self.showWarning = true;
                    }
                    self.timeRemaining = Math.max(0, self.totalTimeout - self.inactivitySeconds);
                }
                
                if (self.inactivitySeconds >= self.totalTimeout) {
                    console.log('❌ Total inactivity timeout reached - auto logout');
                    self.autoLogout();
                }
            }, 1000);
        },

        resetInactivityTimer() {
            this.inactivitySeconds = 0;
            this.showWarning = false;
            this.timeRemaining = 0;
            console.log('🔄 Inactivity timer reset - session will expire in 2 minutes if inactive');
        },

        startPolling() {
            // Polling más frecuente: cada 10 segundos
            // Esto asegura que se verifique la sesión incluso sin interacción en segundo plano
            const self = this;
            this.pollingTimer = setInterval(() => {
                console.log('🔄 Polling session status (every 10s)...');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                fetch('/session/ping', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    }
                })
                .then(response => {
                    if (response.status === 401 || response.status === 419) {
                        console.log('🔴 Server returned 401/419 - session expired on server');
                        self.autoLogout();
                    } else if (!response.ok) {
                        console.warn('⚠️ Unexpected response:', response.status);
                    } else {
                        console.log('✅ Session still valid');
                    }
                    return response.json().catch(() => null);
                })
                .catch(error => {
                    console.error('❌ Polling error (network or server down):', error);
                    // No logout en caso de error de red - podría ser temporal
                });
            }, 10000);
        },

        continueSession() {
            console.log('✅ User clicked "Continue session"');
            const self = this;
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch('/session/ping', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                }
            })
            .then(response => {
                if (response.ok) {
                    console.log('✅ Session extended - resetting timer');
                    self.resetInactivityTimer();
                } else {
                    console.log('❌ Failed to extend session - status:', response.status);
                    self.autoLogout();
                }
            })
            .catch(error => {
                console.error('Error extending session:', error);
                self.autoLogout();
            });
        },

        autoLogout() {
            console.log('🔴 Auto logout - redirecting to login');
            if (this.inactivityTimer) clearInterval(this.inactivityTimer);
            if (this.pollingTimer) clearInterval(this.pollingTimer);
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        },

        logout() {
            console.log('👤 User clicked "Logout"');
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        },

        formatTime(seconds) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/logout';
            const token = document.createElement('input');
            token.type = 'hidden';
            token.name = '_token';
            token.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(token);
            document.body.appendChild(form);
            form.submit();
        },

        formatTime(seconds) {
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
        }
    };
}
</script>
