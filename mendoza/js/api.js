// Class to handle API communications with the backend
class ApiService {
    // Constructor to initialize the base URL for API requests
    constructor() {
        this.baseUrl = '/taller/tallerApi';
    }

    // Generic method to make API requests with error handling
    async request(endpoint, options = {}) {
        // Construct full URL
        const url = `${this.baseUrl}${endpoint}`;
        // Set up request configuration with default headers and credentials
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            credentials: 'include', // Include cookies for session management
            ...options
        };

        try {
            // Make the fetch request
            const response = await fetch(url, config);
            // Parse JSON response
            const data = await response.json();

            // Check if the API response indicates success
            if (!data.success) {
                throw new Error(data.error || 'Error en la API');
            }

            // Return the data portion of the response
            return data.data;
        } catch (error) {
            // Log and re-throw errors
            console.error('API Error:', error);
            throw error;
        }
    }

    // Public endpoints accessible without authentication
    // Get the current state of a taller
    async getEstadoTaller(tallerId) {
        return this.request(`/api/v1/taller/${tallerId}/estado`);
    }

    // Create a new turno for a taller
    async crearTurno(tallerId, turnoData) {
        return this.request(`/api/v1/taller/${tallerId}/turnos`, {
            method: 'POST',
            body: JSON.stringify(turnoData)
        });
    }

    // Admin endpoints requiring authentication
    // Login with credentials
    async login(credentials) {
        return this.request('/api/v1/admin/login', {
            method: 'POST',
            body: JSON.stringify(credentials)
        });
    }

    // List all turnos for a taller with optional filters
    async listarTurnos(tallerId, filtros = {}) {
        const params = new URLSearchParams();
        if (filtros.patente) params.append('patente', filtros.patente);
        const query = params.toString();
        const endpoint = `/api/v1/admin/taller/${tallerId}/turnos${query ? '?' + query : ''}`;
        return this.request(endpoint);
    }

    // Finalize a turno
    async finalizarTurno(turnoId) {
        return this.request(`/api/v1/admin/turno/${turnoId}/finalizar`, {
            method: 'POST'
        });
    }

    // User management endpoints
    // List all usuarios for a taller
    async listarUsuarios(tallerId) {
        return this.request(`/api/v1/admin/taller/${tallerId}/usuarios`);
    }

    // Create a new usuario for a taller
    async crearUsuario(tallerId, usuarioData) {
        return this.request(`/api/v1/admin/taller/${tallerId}/usuarios`, {
            method: 'POST',
            body: JSON.stringify(usuarioData)
        });
    }

    // Update password for a usuario
    async actualizarPasswordUsuario(usuarioId, passwordData) {
        return this.request(`/api/v1/admin/usuario/${usuarioId}/password`, {
            method: 'PUT',
            body: JSON.stringify(passwordData)
        });
    }

    // Delete a usuario
    async eliminarUsuario(usuarioId) {
        return this.request(`/api/v1/admin/usuario/${usuarioId}`, {
            method: 'DELETE'
        });
    }

    // Endpoints configuración email
    async obtenerConfiguracionEmail(tallerId) {
        return this.request(`/api/v1/admin/taller/${tallerId}/configuracion-email`);
    }

    async guardarConfiguracionEmail(tallerId, configData) {
        return this.request(`/api/v1/admin/taller/${tallerId}/configuracion-email`, {
            method: 'POST',
            body: JSON.stringify(configData)
        });
    }

    async probarConfiguracionEmail(tallerId) {
        return this.request(`/api/v1/admin/taller/${tallerId}/probar-email`, {
            method: 'POST'
        });
    }

    // Retrieve the email configuration
    async getConfiguracionEmail() {
        return this.request('/api/v1/admin/configuracion-email');
    }

    // Update the email configuration
    async updateConfiguracionEmail(data) {
        return this.request('/api/v1/admin/configuracion-email', {
            method: 'POST',
            body: JSON.stringify(data)
        });
    }

    // Create a new taller
    async crearTaller(tallerData) {
        return this.request('/api/v1/admin/talleres', {
            method: 'POST',
            body: JSON.stringify(tallerData)
        });
    }
}