import React from 'react';
import { ErdData } from '../types/erd';

interface ErdHeaderProps {
  erdData: ErdData;
  onRefresh: () => void;
  loading?: boolean;
}

/**
 * Header component for the ERD diagram
 */
const ErdHeader: React.FC<ErdHeaderProps> = ({ erdData, onRefresh, loading = false }) => {
  return (
    <div className="bg-white border-b border-gray-200 px-6 py-4 flex-shrink-0">
      <div className="flex justify-between items-center">
        <h1 className="text-2xl font-bold text-gray-900">
          Entity Relationship Diagram
        </h1>
        <div className="flex items-center space-x-4">
          <span className="text-sm text-gray-600">
            {erdData.tables.length} tables, {erdData.relationships.length} relationships
          </span>
          <button
            onClick={onRefresh}
            disabled={loading}
            className={`px-3 py-1 rounded text-sm transition-colors ${
              loading
                ? 'bg-gray-400 text-white cursor-not-allowed'
                : 'bg-blue-600 text-white hover:bg-blue-700'
            }`}
          >
            {loading ? 'Refreshing...' : 'Refresh'}
          </button>
        </div>
      </div>
    </div>
  );
};

export default ErdHeader;