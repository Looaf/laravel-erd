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

interface ErdDiagramProps {
  config?: ErdConfig;
}

/**
 * Main ERD diagram flow component
 */
const ErdDiagramFlow: React.FC<ErdDiagramProps> = ({ config }) => {
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
    <div className="erd-container h-screen flex flex-col">
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