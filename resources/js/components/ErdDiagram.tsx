import React, { useCallback } from 'react';
import { ReactFlowProvider, Node } from 'reactflow';
import 'reactflow/dist/style.css';
import { useErdData } from '../hooks/useErdData';
import { ErdConfig } from '../utils/apiUtils';
import FlowCanvas from './FlowCanvas';
import ErdHeader from './ErdHeader';
import LoadingState from './LoadingState';
import ErrorState from './ErrorState';
import EmptyState from './EmptyState';
import DebugPanel from './DebugPanel';

interface ErdDiagramProps {
  config?: ErdConfig;
}

/**
 * Main ERD diagram flow component
 */
const ErdDiagramFlow: React.FC<ErdDiagramProps> = ({ config }) => {
  const [showDebug, setShowDebug] = React.useState(false);
  
  const {
    erdData,
    loading,
    error,
    nodes,
    edges,
    setNodes,
    setEdges,
    refreshData,
    updateNodePosition,
  } = useErdData(config);

  // Handle node drag end - persist position changes
  const handleNodeDragStop = useCallback((event: any, node: Node) => {
    updateNodePosition(node.id, node.position);
  }, [updateNodePosition]);

  // Loading state
  if (loading) {
    return <LoadingState />;
  }

  // Error state
  if (error) {
    return <ErrorState error={error} onRetry={refreshData} />;
  }

  // Empty state
  if (!erdData || erdData.tables.length === 0) {
    return <EmptyState onRefresh={refreshData} />;
  }

  // Main diagram view
  return (
    <div className="erd-container h-screen flex flex-col relative">
      <ErdHeader 
        erdData={erdData} 
        onRefresh={refreshData}
        loading={loading}
      />
      <FlowCanvas
        erdData={erdData}
        nodes={nodes}
        edges={edges}
        setNodes={setNodes}
        setEdges={setEdges}
        onNodeDragStop={handleNodeDragStop}
      />
      
      {/* Debug toggle button */}
      <button
        onClick={() => setShowDebug(!showDebug)}
        className="fixed bottom-4 right-4 bg-red-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-red-700 z-40"
        title="Toggle Debug Panel"
      >
        🐛 Debug
      </button>
      
      {/* Debug panel - temporary for debugging */}
      {showDebug && (
        <DebugPanel erdData={erdData} nodes={nodes} edges={edges} />
      )}
    </div>
  );
};

/**
 * Main wrapper component with ReactFlowProvider
 */
const ErdDiagram: React.FC<ErdDiagramProps> = (props) => {
  return (
    <ReactFlowProvider>
      <ErdDiagramFlow {...props} />
    </ReactFlowProvider>
  );
};

export default ErdDiagram;