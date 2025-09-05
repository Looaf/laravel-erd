import { createRoot } from 'react-dom/client';
import '../css/app.css';
import { ErdDiagram } from './components';

// Extend window interface for TypeScript
declare global {
    interface Window {
        ErdConfig: {
            apiEndpoint: string;
            refreshEndpoint: string;
            csrfToken: string;
            routes: {
                data: string;
                refresh: string;
            };
        };
    }
}

// Initialize React app when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('erd-app');
    if (container) {
        const root = createRoot(container);
        
        // Pass configuration from Laravel to React
        const config = window.ErdConfig || {
            apiEndpoint: '/erd/data',
            refreshEndpoint: '/erd/refresh',
            csrfToken: '',
            routes: {
                data: '/erd/data',
                refresh: '/erd/refresh'
            }
        };
        
        root.render(<ErdDiagram config={config} />);
    }
});