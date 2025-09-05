import React from 'react';

interface EmptyStateProps {
  onRefresh: () => void;
}

/**
 * Empty state component when no models are found
 */
const EmptyState: React.FC<EmptyStateProps> = ({ onRefresh }) => {
  return (
    <div className="erd-container p-8">
      <div className="text-center max-w-md mx-auto">
        <h3 className="text-xl font-semibold text-gray-700 mb-4">
          No Models Found
        </h3>
        <p className="text-gray-600 mb-4">
          No Eloquent models were found in your application. Make sure you have models
          in your app/Models directory with proper relationships defined.
        </p>
        <button
          onClick={onRefresh}
          className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
        >
          Refresh
        </button>
      </div>
    </div>
  );
};

export default EmptyState;