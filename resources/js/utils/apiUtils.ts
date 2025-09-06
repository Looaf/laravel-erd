/**
 * API utility functions for ERD data fetching
 */

export interface ErdConfig {
  apiEndpoint: string;
  refreshEndpoint: string;
  csrfToken: string;
  routes: {
    data: string;
    refresh: string;
  };
}

/**
 * Get ERD configuration from props or window object
 */
export const getErdConfig = (config?: ErdConfig): ErdConfig => {
  return config || (window as any).ErdConfig || {
    apiEndpoint: '/erd/data',
    refreshEndpoint: '/erd/refresh',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    routes: {
      data: '/erd/data',
      refresh: '/erd/refresh'
    }
  };
};

/**
 * Fetch ERD data from the API
 */
export const fetchErdData = async (config: ErdConfig): Promise<any> => {

  const response = await fetch(config.routes.data, {
    headers: {
      'X-CSRF-TOKEN': config.csrfToken,
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  });


  if (!response.ok) {
    throw new Error(`Failed to fetch ERD data: ${response.statusText}`);
  }

  const result = await response.json();

  return result;
};

/**
 * Refresh ERD data (placeholder for future implementation)
 */
export const refreshErdData = async (config: ErdConfig): Promise<any> => {
  // For now, just refetch the data instead of calling a separate refresh endpoint
  // This avoids CSRF issues until the backend refresh endpoint is properly implemented
  return fetchErdData(config);
};