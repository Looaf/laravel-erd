import { useState, useEffect, useCallback } from 'react';
import { Node, Edge } from 'reactflow';
import { ErdData } from '../types/erd';
import { ErdConfig, getErdConfig, fetchErdData } from '../utils/apiUtils';
import { 
  convertTablesToNodes, 
  convertRelationshipsToEdges, 
  processApiResponse,
  createTestEdge 
} from '../utils/erdDataUtils';
import { debugLog, debugError, debugFlowData } from '../utils/debugUtils';

/**
 * Custom hook for managing ERD data state and API calls
 */
export const useErdData = (config?: ErdConfig) => {
  const [erdData, setErdData] = useState<ErdData | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string | null>(null);
  const [nodes, setNodes] = useState<Node[]>([]);
  const [edges, setEdges] = useState<Edge[]>([]);

  const erdConfig = getErdConfig(config);

  const loadErdData = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const result = await fetchErdData(erdConfig);
      const processedData = processApiResponse(result);

      setErdData(processedData);

      // Convert to React Flow format
      const flowNodes = convertTablesToNodes(processedData);
      const flowEdges = convertRelationshipsToEdges(processedData.relationships);

      debugLog('HOOK', 'Converting to React Flow format');
      debugFlowData(flowNodes, flowEdges);

      setNodes(flowNodes);
      setEdges(flowEdges);

      // Debug: If no edges but we have nodes, create a test edge
      if (flowEdges.length === 0 && flowNodes.length >= 2) {
        const testEdge = createTestEdge(flowNodes);
        if (testEdge) {
          setEdges([testEdge]);
        }
      }
    } catch (err) {
      debugError('HOOK', 'Error in loadErdData', err);
      setError(err instanceof Error ? err.message : 'An unknown error occurred');
    } finally {
      setLoading(false);
      debugLog('HOOK', 'Fetch complete, loading set to false');
    }
  }, [erdConfig]);

  const refreshData = useCallback(async () => {
    debugLog('HOOK', 'Refresh requested, reloading data...');
    await loadErdData();
  }, [loadErdData]);

  const updateNodePosition = useCallback((nodeId: string, position: { x: number; y: number }) => {
    debugLog('HOOK', `Node ${nodeId} moved to position: ${position.x}, ${position.y}`);

    if (erdData) {
      const updatedTables = erdData.tables.map(table =>
        table.id === nodeId
          ? { ...table, position }
          : table
      );

      setErdData({
        ...erdData,
        tables: updatedTables
      });
    }
  }, [erdData]);

  useEffect(() => {
    debugLog('HOOK', 'useErdData hook initialized');
    debugLog('HOOK', 'Config loaded', erdConfig);
    loadErdData();
  }, [loadErdData]);

  return {
    erdData,
    loading,
    error,
    nodes,
    edges,
    setNodes,
    setEdges,
    refreshData,
    updateNodePosition,
  };
};