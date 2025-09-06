import React, { useState } from 'react';
import { useReactFlow } from 'reactflow';
import { ZoomIn, ZoomOut, Maximize2, RotateCcw } from 'lucide-react';

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
          <ZoomIn size={16} />
        </button>
        <button
          onClick={handleZoomOut}
          className="px-3 py-2 hover:bg-gray-50 border-b border-gray-200 transition-colors"
          title="Zoom Out"
        >
          <ZoomOut size={16} />
        </button>
        <button
          onClick={handleFitView}
          className="px-3 py-2 hover:bg-gray-50 border-b border-gray-200 transition-colors"
          title="Fit to View"
        >
          <Maximize2 size={16} />
        </button>
        <button
          onClick={handleResetZoom}
          className="px-3 py-2 hover:bg-gray-50 rounded-b-lg transition-colors"
          title="Reset Zoom"
        >
          <RotateCcw size={16} />
        </button>
      </div>
    </div>
  );
};

export default ZoomControls;