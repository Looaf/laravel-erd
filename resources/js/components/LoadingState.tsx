import React from 'react';

/**
 * Loading state component for ERD diagram
 */
const LoadingState: React.FC = () => {
  console.log('⏳ Rendering loading state');
  
  return (
    <div className="erd-loading">
      <div className="flex flex-col items-center space-y-4">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <p className="text-gray-600">Loading ERD data...</p>
      </div>
    </div>
  );
};

export default LoadingState;