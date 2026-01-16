// Import Vue.js composition API functions for reactive state and lifecycle management
const { createApp, ref, reactive, onMounted, computed } = Vue;

createApp({
    setup() {
        // Reactive state for current logged-in user
        const user = ref(null);
        // Reactive state for list of turnos (appointments)
        const turnos = ref([]);
        // Reactive state for list of usuarios (users)
        const usuarios = ref([]);
        // Reactive state for email configuration
        const emailConfig = reactive({
            smtpHost: 'smtp.hostinger.com',
            smtpPort: 465,
            smtpUsuario: '',
            smtpPassword: '',
            emailOrigen: '',
            nombreOrigen: '',
            emailDestino: '',
            activo: false
        });
        // Reactive state for loading indicator
        const loading = ref(false);
        // Reactive state for email-specific loading indicator
        const loadingEmail = ref(false);
        // Reactive state for error messages
        const error = ref(null);
        // Reactive state for email-specific error messages
        const errorEmail = ref('');
        // Reactive state for active tab in UI
        const activeTab = ref('turnos');
        // Reactive state to show/hide user creation form
        const showUserForm = ref(false);
        // Reactive state to show/hide password update form
        const showPasswordForm = ref(false);
        // Reactive state to show/hide email configuration form
        const showEmailForm = ref(false);
        // Reactive state for selected user in password form
        const selectedUser = ref(null);
        // Reactive state for turnos filters
        const filtroPatente = ref('');

        // Reactive form data for login
        const loginForm = reactive({
            usuario: '',
            password: ''
        });

        // Reactive form data for creating new user
        const userForm = reactive({
            usuario: '',
            password: ''
        });

        // Reactive form data for updating password
        const passwordForm = reactive({
            password: ''
        });

        // Instance of API service for backend communication
        const api = new ApiService();

        // Computed property to check if user is logged in
        const isLoggedIn = computed(() => user.value !== null);

        // Function to handle user login process
        const login = async () => {
            try {
                // Set loading state and clear previous errors
                loading.value = true;
                error.value = null;

                // Call API to authenticate user
                const userData = await api.login(loginForm);
                // Set authenticated user data
                user.value = userData;

                // Clear login form fields
                loginForm.usuario = '';
                loginForm.password = '';

                // Load turnos after successful login
                await cargarTurnos();
            } catch (err) {
                // Set error message on failure
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };

        // Function to handle user logout
        const logout = () => {
            // Clear user data, turnos, and errors
            user.value = null;
            turnos.value = [];
            error.value = null;
        };

        // Function to load turnos from API with optional filters
        const cargarTurnos = async (filtros = {}) => {
            // Exit if no user is logged in
            if (!user.value) return;

            try {
                // Set loading state and clear errors
                loading.value = true;
                error.value = null;
                // Fetch turnos for the user's taller with filters
                const data = await api.listarTurnos(user.value.tallerId, filtros);
                // Update turnos list
                turnos.value = data.turnos;
            } catch (err) {
                // Set error message
                error.value = err.message;
                // Logout if unauthorized
                if (err.message.includes('no autorizado')) {
                    logout();
                }
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };

        // Function to finalize a turno
        const finalizarTurno = async (turnoId) => {
            try {
                // Set loading state and clear errors
                loading.value = true;
                error.value = null;

                // Call API to finalize turno
                await api.finalizarTurno(turnoId);

                // Reload turnos to reflect changes
                await cargarTurnos();

                // Show success message
                showSuccess('Turno finalizado correctamente');
            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };

        // Function to display success toast message using Bootstrap
        const showSuccess = (message) => {
            // Create Bootstrap toast element for success notification
            const toastEl = document.createElement('div');
            toastEl.className = 'toast align-items-center text-white bg-success border-0';
            toastEl.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            // Append toast to body and show it
            document.body.appendChild(toastEl);
            const toast = new bootstrap.Toast(toastEl);
            toast.show();

            // Remove toast from DOM after it's hidden
            toastEl.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toastEl);
            });
        };

        // Function to get CSS class for turno estado text color
        const getEstadoClass = (estado) => {
            switch (estado) {
                case 'EN_TALLER': return 'text-success'; // Green for in workshop
                case 'EN_ESPERA': return 'text-warning'; // Yellow for waiting
                case 'FINALIZADO': return 'text-muted'; // Muted for finished
                default: return 'text-secondary'; // Default secondary color
            }
        };

        // Function to get CSS class for turno estado badge
        const getEstadoBadgeClass = (estado) => {
            switch (estado) {
                case 'EN_TALLER': return 'bg-success'; // Green badge for in workshop
                case 'EN_ESPERA': return 'bg-warning'; // Yellow badge for waiting
                case 'FINALIZADO': return 'bg-secondary'; // Gray badge for finished
                default: return 'bg-light text-dark'; // Light badge for unknown
            }
        };

        // Function to check if a turno can be finalized (only if in workshop)
        const puedeFinalizarTurno = (turno) => {
            return turno.estado === 'EN_TALLER';
        };

        // Function to format date and time for display
        const formatDateTime = (dateString) => {
            if (!dateString) return '-'; // Return dash if no date
            return new Date(dateString).toLocaleString('es-ES'); // Format in Spanish locale
        };

        // === USER MANAGEMENT SECTION ===

        // Function to load usuarios from API
        const cargarUsuarios = async () => {
            // Exit if no user is logged in
            if (!user.value) return;

            try {
                // Set loading state
                loading.value = true;
                // Fetch usuarios for the user's taller
                const data = await api.listarUsuarios(user.value.tallerId);
                // Update usuarios list
                usuarios.value = data.usuarios;
            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };
        
        // Function to create a new usuario
        const crearUsuario = async () => {
            try {
                // Set loading state and clear errors
                loading.value = true;
                error.value = null;

                // Call API to create usuario
                await api.crearUsuario(user.value.tallerId, userForm);

                // Clear form and hide it
                Object.keys(userForm).forEach(key => userForm[key] = '');
                showUserForm.value = false;

                // Reload usuarios to reflect changes
                await cargarUsuarios();

                // Show success message
                showSuccess('Usuario creado correctamente');
            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };

        // Function to update password for a selected usuario
        const actualizarPassword = async () => {
            try {
                // Set loading state and clear errors
                loading.value = true;
                error.value = null;

                // Call API to update password
                await api.actualizarPasswordUsuario(selectedUser.value.id, passwordForm);

                // Clear form and hide it, reset selected user
                passwordForm.password = '';
                showPasswordForm.value = false;
                selectedUser.value = null;

                // Show success message
                showSuccess('Contraseña actualizada correctamente');
            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };

        // Function to delete a usuario
        const eliminarUsuario = async (usuario) => {
            // Confirm deletion with user
            if (!confirm(`¿Estás seguro de eliminar al usuario "${usuario.usuario}"?`)) {
                return;
            }

            try {
                // Set loading state and clear errors
                loading.value = true;
                error.value = null;

                // Call API to delete usuario
                await api.eliminarUsuario(usuario.id);

                // Reload usuarios to reflect changes
                await cargarUsuarios();

                // Show success message
                showSuccess('Usuario eliminado correctamente');
            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset loading state
                loading.value = false;
            }
        };
        
        // Function to open password update form for a usuario
        const abrirFormPassword = (usuario) => {
            // Set selected user and reset form
            selectedUser.value = usuario;
            passwordForm.password = '';
            showPasswordForm.value = true;
        };

        // Function to close all forms and reset state
        const cerrarFormularios = () => {
            // Hide forms and reset related state
            showUserForm.value = false;
            showPasswordForm.value = false;
            showEmailForm.value = false;
            selectedUser.value = null;
            Object.keys(userForm).forEach(key => userForm[key] = '');
            passwordForm.password = '';
            error.value = null;
            errorEmail.value = null;
        };

        // Function to apply filters to turnos
        const aplicarFiltros = async () => {
            const filtros = {};
            if (filtroPatente.value.trim()) {
                filtros.patente = filtroPatente.value.trim();
            }
            await cargarTurnos(filtros);
        };

        // Function to clear filters
        const limpiarFiltros = async () => {
            filtroPatente.value = '';
            await cargarTurnos();
        };

        // === EMAIL CONFIGURATION ===
        
        const cargarConfiguracionEmail = async () => {
            if (!user.value) return;

            try {
                const data = await api.obtenerConfiguracionEmail(user.value.tallerId);
                if (data.configuracion) {
                    Object.assign(emailConfig, data.configuracion);
                }
            } catch (err) {
                console.log('No hay configuración de email');
            }
        };
        
        const guardarConfiguracionEmail = async () => {
            try {
                loadingEmail.value = true;
                errorEmail.value = null;

                await api.guardarConfiguracionEmail(user.value.tallerId, emailConfig);

                showEmailForm.value = false;
                await cargarConfiguracionEmail();

                showSuccess('Configuración de email guardada correctamente');
            } catch (err) {
                errorEmail.value = err.message;
            } finally {
                loadingEmail.value = false;
            }
        };
        
        const probarEmail = async () => {
            try {
                loadingEmail.value = true;
                errorEmail.value = null;

                await api.probarConfiguracionEmail(user.value.tallerId);

                showSuccess('Email de prueba enviado correctamente');
            } catch (err) {
                errorEmail.value = err.message;
            } finally {
                loadingEmail.value = false;
            }
        };

        // Watch for changes in filtroPatente to apply filters
        Vue.watch(filtroPatente, aplicarFiltros);

        // Lifecycle hook to run on component mount
        onMounted(() => {
            // Check for active session by attempting to load turnos
            cargarTurnos().then(() => {
                // If user is set, load usuarios as well
                if (user.value) {
                    cargarUsuarios();
                    cargarConfiguracionEmail();
                }
            });
        });

        // Return reactive state and functions for template binding
        return {
            user,
            turnos,
            usuarios,
            emailConfig,
            loading,
            loadingEmail,
            error,
            errorEmail,
            activeTab,
            showUserForm,
            showPasswordForm,
            showEmailForm,
            selectedUser,
            loginForm,
            userForm,
            passwordForm,
            filtroPatente,
            isLoggedIn,
            login,
            logout,
            cargarTurnos,
            cargarUsuarios,
            cargarConfiguracionEmail,
            finalizarTurno,
            crearUsuario,
            actualizarPassword,
            eliminarUsuario,
            abrirFormPassword,
            cerrarFormularios,
            aplicarFiltros,
            limpiarFiltros,
            guardarConfiguracionEmail,
            probarEmail,
            getEstadoClass,
            getEstadoBadgeClass,
            puedeFinalizarTurno,
            formatDateTime
        };
    }
}).mount('#app'); // Mount Vue app to element with id 'app'