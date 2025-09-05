import React, { useState, useEffect } from 'react';
import { ErdData } from '../types/erd';
import TableNode from './TableNode';

interface ErdConfig {
  apiEndpoint: string;
  refreshEndpoint: string;
  csrfToken: string;
  routes: {
    data: string;
    refresh: string;
  };
}

interface ErdDiagramProps {
  config?: ErdConfig;
}

const ErdDiagram: React.FC<ErdDiagramProps> = ({ config }) => {
  // Use window.ErdConfig as fallback if config prop is not provided
  const erdConfig = config || (window as any).ErdConfig || {
    apiEndpoint: '/erd/data',
    refreshEndpoint: '/erd/refresh',
    csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
    routes: {
      data: '/erd/data',
      refresh: '/erd/refresh'
    }
  };
  const [erdData, setErdData] = useState<ErdData | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    console.log('🚀 ErdDiagram component mounted');
    console.log('⚙️ Config:', erdConfig);
    fetchErdData();
  }, []);

  const fetchErdData = async () => {
    try {
      console.log('🔄 Starting ERD data fetch...');
      console.log('📍 Fetch URL:', erdConfig.routes.data);
      console.log('🔑 CSRF Token:', erdConfig.csrfToken ? 'Present' : 'Missing');
      
      setLoading(true);
      setError(null);

      const response = await fetch(erdConfig.routes.data, {
        headers: {
          'X-CSRF-TOKEN': erdConfig.csrfToken,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      console.log('📡 Response status:', response.status);
      console.log('📡 Response headers:', Object.fromEntries(response.headers.entries()));

      if (!response.ok) {
        console.error('❌ Response not OK:', response.status, response.statusText);
        throw new Error(`Failed to fetch ERD data: ${response.statusText}`);
      }

      const result = await response.json();
      console.log('📦 Raw response data:', result);
      console.log('📊 Response type:', typeof result);
      console.log('📊 Response keys:', Object.keys(result || {}));

      // Handle Laravel response format
      if (result.success && result.data) {
        console.log('✅ Using result.data (success format)');
        console.log('📋 Tables found:', result.data.tables?.length || 0);
        console.log('🔗 Relationships found:', result.data.relationships?.length || 0);
        setErdData(result.data);
      } else if (result.data) {
        console.log('✅ Using result.data (direct format)');
        console.log('📋 Tables found:', result.data.tables?.length || 0);
        console.log('🔗 Relationships found:', result.data.relationships?.length || 0);
        setErdData(result.data);
      } else if (result.tables) {
        console.log('✅ Using result directly (tables format)');
        console.log('📋 Tables found:', result.tables?.length || 0);
        console.log('🔗 Relationships found:', result.relationships?.length || 0);
        setErdData(result);
      } else {
        console.error('❌ No valid data structure found in response');
        console.log('🔍 Available keys:', Object.keys(result || {}));
        throw new Error(result.message || 'No data received');
      }
    } catch (err) {
      console.error('💥 Error in fetchErdData:', err);
      setError(err instanceof Error ? err.message : 'An unknown error occurred');
    } finally {
      setLoading(false);
      console.log('🏁 Fetch complete, loading set to false');
    }
  };

  const handleRefresh = async () => {
    console.log('🔄 Refresh button clicked, refetching data...');
    // For now, just refetch the data instead of calling a separate refresh endpoint
    // This avoids CSRF issues until the backend refresh endpoint is properly implemented
    await fetchErdData();
  };

  if (loading) {
    console.log('⏳ Rendering loading state');
    return (
      <div className="erd-loading">
        <div className="flex flex-col items-center space-y-4">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-erd-primary"></div>
          <p className="text-erd-secondary">Loading ERD data...</p>
        </div>
      </div>
    );
  }

  if (error) {
    console.log('❌ Rendering error state:', error);
    return (
      <div className="erd-container p-8">
        <div className="erd-error max-w-md mx-auto">
          <h3 className="font-semibold text-lg mb-2">Error Loading ERD</h3>
          <p className="mb-4">{error}</p>
          <button
            onClick={handleRefresh}
            className="bg-erd-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  if (!erdData || erdData.tables.length === 0) {
    return (
      <div className="erd-container p-8">
        <div className="text-center max-w-md mx-auto">
          <h3 className="text-xl font-semibold text-erd-secondary mb-4">
            No Models Found
          </h3>
          <p className="text-erd-secondary mb-4">
            No Eloquent models were found in your application. Make sure you have models
            in your app/Models directory with proper relationships defined.
          </p>
          <button
            onClick={handleRefresh}
            className="bg-erd-primary text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
          >
            Refresh
          </button>
        </div>
      </div>
    );
  }

  return (
    <div className="erd-container">
      <div className="bg-white border-b border-gray-200 px-6 py-4">
        <div className="flex justify-between items-center">
          <h1 className="text-2xl font-bold text-gray-900">
            Entity Relationship Diagram
          </h1>
          <div className="flex items-center space-x-4">
            <span className="text-sm text-erd-secondary">
              {erdData.tables.length} tables, {erdData.relationships.length} relationships
            </span>
            <button
              onClick={handleRefresh}
              className="bg-erd-primary text-white px-3 py-1 rounded text-sm hover:bg-blue-600 transition-colors"
            >
              Refresh
            </button>
          </div>
        </div>
      </div>

      <div className="p-6 bg-gray-50 min-h-screen">
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {erdData.tables.map((table) => (
            <TableNode
              key={table.id}
              table={table}
              relationships={erdData.relationships.filter(
                rel => rel.source === table.id || rel.target === table.id
              )}
            />
          ))}
        </div>
      </div>
    </div>
  );
};

export default ErdDiagram;