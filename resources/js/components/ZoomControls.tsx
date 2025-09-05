import React, { useState } from 'react';
import { useReactFlow } from 'reactflow';

/**
 * Custom zoom controls component for React Flow
 */
const ZoomControls: React.FC = () => {
  const { zoomIn, zoomOut, fitView, getZoom } = useReactFlow();
  const [currentZoom, setCurrentZoom] = useState(0.8);

  const handleZoomIn = () => {
    zoomIn();
    setCurrentZoom(getZoom());
  };

  const handleZoomOut = () => {
    zoomOut();
    setCurrentZoom(getZoom());
  };

  const handleFitView = () => {
    fitView({ padding: 0.2 });
    setCurrentZoom(getZoom());
  };

  const handleResetZoom = () => {
    fitView({ padding: 0.1, minZoom: 0.8, maxZoom: 0.8 });
    setCurrentZoom(0.8);
  };

  return (
    <div className="absolute top-4 right-4 z-10 bg-white border border-gray-300 rounded-lg shadow-sm">
      <div className="flex flex-col">
        <button
          onClick={handleZoomIn}
          className="px-3 py-2 hover:bg-gray-50 border-b border-gray-200 rounded-t-lg transition-colors"
          title="Zoom In"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
        </button>
        <button
          onClick={handleZoomOut}
          className="px-3 py-2 hover:bg-gray-50 border-b border-gray-200 transition-colors"
          title="Zoom Out"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
          </svg>
        </button>
        <button
          onClick={handleFitView}
          className="px-3 py-2 hover:bg-gray-50 border-b border-gray-200 transition-colors"
          title="Fit to View"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
          </svg>
        </button>
        <button
          onClick={handleResetZoom}
          className="px-3 py-2 hover:bg-gray-50 rounded-b-lg transition-colors"
          title="Reset Zoom"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
        </button>
      </div>
      <div className="px-3 py-1 text-xs text-gray-500 border-t border-gray-200 text-center">
        {Math.round(currentZoom * 100)}%
      </div>
    </div>
  );
};

export default ZoomControls;