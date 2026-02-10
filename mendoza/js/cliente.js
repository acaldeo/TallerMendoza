// Import Vue.js composition API functions for reactive state and lifecycle management
const { createApp, ref, reactive, onMounted, onUnmounted } = Vue;

createApp({
    setup() {
        // Reactive state for taller status data
        const estado = ref(null);
        // Reactive state for loading indicator
        const loading = ref(true);
        // Reactive state for error messages
        const error = ref(null);
        // Reactive state for taller ID (default 1)
        const tallerId = ref(1);
        // Reactive state for talleres list
        const talleres = ref([]);
        // Reactive state for selected taller
        const tallerSeleccionado = ref(null);
        // Reactive state for auto-refresh interval ID
        const autoRefreshInterval = ref(null);
        // Reactive state for last update timestamp
        const lastUpdate = ref(new Date());
        // Reactive state to show/hide turno creation form
        const showForm = ref(false);
        // Reactive state for turno creation loading
        const creatingTurno = ref(false);
        // Reactive state for success messages
        const successMessage = ref('');
        const showSuccessModal = ref(false);
        const turnoCreado = ref(null);
        // Reactive state for silent refresh indicator
        const refreshing = ref(false);

        // Reactive form data for creating a new turno
        const turnoForm = reactive({
            nombreCliente: '',
            telefono: '',
            modeloVehiculo: '',
            patente: '',
            descripcionProblema: ''
        });

        // Instance of API service for backend communication
        const api = new ApiService();

        // Function to load taller estado from API
        const cargarEstado = async (silencioso = false) => {
            try {
                // Set appropriate loading state based on silent flag
                if (!silencioso) {
                    loading.value = true;
                } else {
                    refreshing.value = true;
                }
                // Clear previous errors
                error.value = null;
                // Fetch estado data from API
                const data = await api.getEstadoTaller(tallerId.value);
                // Update estado and last update time
                estado.value = data;
                lastUpdate.value = new Date();
            } catch (err) {
                // Set error message and log to console
                error.value = err.message;
                console.error('Error cargando estado:', err);
            } finally {
                // Reset loading state
                if (!silencioso) {
                    loading.value = false;
                } else {
                    refreshing.value = false;
                }
            }
        };

        // Function to load talleres list from API
        const cargarTalleres = async () => {
            try {
                const data = await api.listarTalleres();
                talleres.value = data;
                // Auto-select first taller if none selected
                if (data.length > 0 && !tallerSeleccionado.value) {
                    tallerSeleccionado.value = data[0];
                    tallerId.value = data[0].id;
                    cargarEstado();
                }
            } catch (err) {
                console.error('Error cargando talleres:', err);
            }
        };

        // Function to select a taller
        const seleccionarTaller = (taller) => {
            tallerSeleccionado.value = taller;
            tallerId.value = taller.id;
            showForm.value = false;
            cargarEstado();
        };

        // Function to start auto-refresh of taller estado every 8 seconds
        const iniciarAutoRefresh = () => {
            autoRefreshInterval.value = setInterval(() => {
                // Do not update if form is open
                if (!showForm.value) {
                    cargarEstado(true); // Silent update
                }
            }, 8000);
        };

        // Function to stop auto-refresh
        const detenerAutoRefresh = () => {
            if (autoRefreshInterval.value) {
                clearInterval(autoRefreshInterval.value);
                autoRefreshInterval.value = null;
            }
        };

        // Function to get CSS class for turno estado text color
        const getEstadoClass = (estado) => {
            return estado === 'EN_TALLER' ? 'text-success' : 'text-danger';
        };

        // Function to get CSS class for turno estado badge
        const getEstadoBadgeClass = (estado) => {
            return estado === 'EN_TALLER' ? 'bg-success' : 'bg-danger';
        };

        // Function to format date to time string in Spanish locale
        const formatTime = (date) => {
            return date.toLocaleTimeString('es-ES', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        };

        // Function to create a new turno
        const crearTurno = async () => {
            try {
                // Set creating state and clear errors/messages
                creatingTurno.value = true;
                error.value = null;
                successMessage.value = '';

                // Call API to create turno
                const nuevoTurno = await api.crearTurno(tallerId.value, turnoForm);

                // Clear form and hide it
                Object.keys(turnoForm).forEach(key => turnoForm[key] = '');
                showForm.value = false;

                // Store turno data and show success modal
                turnoCreado.value = nuevoTurno;
                showSuccessModal.value = true;

                // Reload estado to reflect changes
                await cargarEstado(); // Visible update after creating turno

            } catch (err) {
                // Set error message
                error.value = err.message;
            } finally {
                // Reset creating state
                creatingTurno.value = false;
            }
        };

        // Function to close success modal
        const cerrarModalExito = () => {
            showSuccessModal.value = false;
            turnoCreado.value = null;
        };

        // Function to toggle the turno creation form visibility
        const toggleForm = () => {
            showForm.value = !showForm.value;
            if (!showForm.value) {
                // Clear form when closing
                Object.keys(turnoForm).forEach(key => turnoForm[key] = '');
                error.value = null;
                // Resume auto-refresh and update immediately
                cargarEstado(); // Visible update when closing form
            }
        };

        // Lifecycle hook to run on component mount
        onMounted(() => {
            cargarTalleres();
            iniciarAutoRefresh();
        });

        // Lifecycle hook to run on component unmount
        onUnmounted(() => {
            detenerAutoRefresh();
        });

        // Return reactive state and functions for template binding
        return {
            estado,
            loading,
            error,
            tallerId,
            talleres,
            tallerSeleccionado,
            lastUpdate,
            showForm,
            creatingTurno,
            successMessage,
            showSuccessModal,
            turnoCreado,
            refreshing,
            turnoForm,
            cargarEstado,
            cargarTalleres,
            seleccionarTaller,
            crearTurno,
            cerrarModalExito,
            toggleForm,
            getEstadoClass,
            getEstadoBadgeClass,
            formatTime
        };
    }
}).mount('#app'); // Mount Vue app to element with id 'app'