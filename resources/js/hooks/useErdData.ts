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
      const availableNodeIds = processedData.tables.map(t => t.id);
      const flowEdges = convertRelationshipsToEdges(processedData.relationships, availableNodeIds);

      setNodes(flowNodes);
      setEdges(flowEdges);

    } catch (err) {
      setError(err instanceof Error ? err.message : 'An unknown error occurred');
    } finally {
      setLoading(false);
    }
  }, [erdConfig]);

  const refreshData = useCallback(async () => {
    await loadErdData();
  }, [loadErdData]);

  const updateNodePosition = useCallback((nodeId: string, position: { x: number; y: number }) => {

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