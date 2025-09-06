import React, { useCallback } from 'react';
import ReactFlow, {
  Node,
  Edge,
  addEdge,
  useNodesState,
  useEdgesState,
  Background,
  BackgroundVariant,
  Connection,
  NodeTypes,
  MiniMap,
  ConnectionLineType,
} from 'reactflow';
import { Grid3X3 } from 'lucide-react';
import { ErdData } from '../types/erd';
import { calculateAutoLayout } from '../utils/erdDataUtils';
import TableNode from './node/TableNode';
import RelationshipLine from './RelationshipLine';
import ZoomControls from './ZoomControls';

interface FlowCanvasProps {
  erdData: ErdData;
  nodes: Node[];
  edges: Edge[];
  setNodes: (nodes: Node[]) => void;
  setEdges: (edges: Edge[]) => void;
  onNodeDragStop: (event: any, node: Node) => void;
}

// Custom React Flow node component that wraps our TableNode
const CustomTableNode = ({ data }: { data: any }) => {
  return (
    <div className="react-flow-table-node">
      <TableNode table={data.table} relationships={data.relationships} />
    </div>
  );
};

// Define custom node and edge types for React Flow
const nodeTypes: NodeTypes = {
  tableNode: CustomTableNode,
};

const edgeTypes = {
  relationshipEdge: RelationshipLine,
};

/**
 * React Flow canvas component for ERD visualization
 */
const FlowCanvas: React.FC<FlowCanvasProps> = ({
  erdData,
  nodes,
  edges,
  setNodes,
  setEdges,
  onNodeDragStop,
}) => {
  const [flowNodes, setFlowNodes, onNodesChange] = useNodesState(nodes);
  const [flowEdges, setFlowEdges, onEdgesChange] = useEdgesState(edges);

  // Update flow state when props change
  React.useEffect(() => {
    setFlowNodes(nodes);
  }, [nodes, setFlowNodes]);

  React.useEffect(() => {
    setFlowEdges(edges);
  }, [edges, setFlowEdges]);

  // Handle new connections (for future use)
  const onConnect = useCallback(
    (params: Connection) => setFlowEdges((eds) => addEdge(params, eds)),
    [setFlowEdges]
  );

  // Auto-layout function
  const handleAutoLayout = useCallback(() => {
    if (!erdData || erdData.tables.length === 0) return;

    const updatedNodes = calculateAutoLayout(flowNodes, erdData.tables.length);
    setFlowNodes(updatedNodes);
    setNodes(updatedNodes);
  }, [flowNodes, erdData, setFlowNodes, setNodes]);

  return (
    <div className="relative flex-1 bg-gray-50">
      {/* Auto Layout Button */}
      <div className="absolute top-4 left-4 z-10">
        <button
          onClick={handleAutoLayout}
          className="flex gap-2 items-center px-3 py-2 text-sm text-white bg-gray-600 bg-opacity-50 rounded-lg shadow-sm backdrop-blur-lg transition-colors hover:bg-gray-700"
        >
          <Grid3X3 size={16} />
          Auto Layout
        </button>
      </div>

      <ReactFlow
        nodes={flowNodes}
        edges={flowEdges}
        onNodesChange={onNodesChange}
        onEdgesChange={onEdgesChange}
        onConnect={onConnect}
        onNodeDragStop={onNodeDragStop}
        nodeTypes={nodeTypes}
        edgeTypes={edgeTypes}
        fitView
        fitViewOptions={{
          padding: 0.2,
          includeHiddenNodes: false,
        }}
        defaultViewport={{ x: 0, y: 0, zoom: 0.8 }}
        minZoom={0.1}
        maxZoom={3}
        attributionPosition="bottom-left"
        nodesDraggable={true}
        nodesConnectable={false}
        elementsSelectable={true}
        panOnScroll={true}
        panOnScrollSpeed={0.5}
        zoomOnScroll={true}
        zoomOnPinch={true}
        zoomOnDoubleClick={true}
        selectNodesOnDrag={false}
      >
        <ZoomControls />
        <MiniMap
          nodeColor={() => {
            return '#3b82f6';
          }}
          nodeStrokeWidth={3}
          zoomable
          pannable
          position="bottom-right"
          style={{
            backgroundColor: '#f9fafb',
            border: '1px solid #d1d5db',
          }}
        />
        <Background
          variant={BackgroundVariant.Dots}
          gap={20}
          size={1}
        />
      </ReactFlow>
    </div>
  );
};

export default FlowCanvas;