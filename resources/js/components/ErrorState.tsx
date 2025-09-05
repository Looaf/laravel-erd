import React from 'react';

interface ErrorStateProps {
  error: string;
  onRetry: () => void;
}

/**
 * Error state component for ERD diagram
 */
const ErrorState: React.FC<ErrorStateProps> = ({ error, onRetry }) => {
  console.log('❌ Rendering error state:', error);
  
  return (
    <div className="erd-container p-8">
      <div className="erd-error max-w-md mx-auto">
        <h3 className="font-semibold text-lg mb-2">Error Loading ERD</h3>
        <p className="mb-4">{error}</p>
        <button
          onClick={onRetry}
          className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
        >
          Try Again
        </button>
      </div>
    </div>
  );
};

export default ErrorState;