import { Node, Edge, MarkerType } from 'reactflow';
import { ErdData, Table, Relationship } from '../types/erd';
import { debugLog, debugFlowData, debugApiResponse } from './debugUtils';

/**
 * Utility functions for converting ERD data to React Flow format
 */

/**
 * Convert ERD tables to React Flow nodes
 */
export const convertTablesToNodes = (erdData: ErdData): Node[] => {
  debugLog('CONVERT', 'Converting tables to nodes', { tableCount: erdData.tables.length });

  const nodes = erdData.tables.map((table, index) => {
    debugLog('CONVERT', `Processing table: ${table.id} (${table.name})`);

    // Calculate position - use existing position or create a grid layout
    const position = table.position || calculateGridPosition(index);

    return {
      id: table.id,
      type: 'tableNode',
      position,
      data: {
        table,
        relationships: erdData.relationships.filter(
          rel => rel.source === table.id || rel.target === table.id
        ),
      },
      draggable: true,
    };
  });

  debugLog('CONVERT', `Generated ${nodes.length} nodes`);
  return nodes;
};

/**
 * Convert ERD relationships to React Flow edges
 */
export const convertRelationshipsToEdges = (relationships: Relationship[]): Edge[] => {
  debugLog('CONVERT', 'Converting relationships to edges', { relationshipCount: relationships.length });

  const edges = relationships.map((rel) => {
    debugLog('CONVERT', `Processing relationship: ${rel.id} (${rel.source} -> ${rel.target}, type: ${rel.type})`);
    
    return {
      id: rel.id,
      source: rel.source,
      target: rel.target,
      type: 'smoothstep',
      animated: false,
      style: getRelationshipStyle(rel.type),
      label: rel.type,
      labelStyle: {
        fontSize: 12,
        fontWeight: 500,
        fill: '#374151',
      },
      labelBgStyle: {
        fill: '#f9fafb',
        fillOpacity: 0.8,
      },
      data: {
        relationshipType: rel.type,
        sourceKey: rel.sourceKey || 'id',
        foreignKey: rel.foreignKey || 'unknown',
      },
      markerEnd: {
        type: MarkerType.ArrowClosed,
        width: 20,
        height: 20,
        color: getRelationshipColor(rel.type),
      },
    };
  });

  debugLog('CONVERT', `Generated ${edges.length} edges`);
  debugFlowData([], edges);
  return edges;
};

/**
 * Calculate grid position for a table node
 */
export const calculateGridPosition = (index: number, columns: number = 4, spacing: { x: number; y: number } = { x: 350, y: 400 }): { x: number; y: number } => {
  return {
    x: (index % columns) * spacing.x + 50,
    y: Math.floor(index / columns) * spacing.y + 50,
  };
};

/**
 * Calculate auto-layout positions for all nodes
 */
export const calculateAutoLayout = (nodes: Node[], tableCount: number): Node[] => {
  const cols = Math.ceil(Math.sqrt(tableCount));
  
  return nodes.map((node, index) => {
    const col = index % cols;
    const row = Math.floor(index / cols);
    
    return {
      ...node,
      position: {
        x: col * 350 + 50,
        y: row * 450 + 50,
      },
    };
  });
};

/**
 * Get relationship style based on type
 */
export const getRelationshipStyle = (type: string) => {
  const styles = {
    hasOne: {
      stroke: '#10b981', // green
      strokeWidth: 2,
    },
    hasMany: {
      stroke: '#3b82f6', // blue
      strokeWidth: 2,
    },
    belongsTo: {
      stroke: '#f59e0b', // amber
      strokeWidth: 2,
      strokeDasharray: '5,5',
    },
    belongsToMany: {
      stroke: '#8b5cf6', // violet
      strokeWidth: 3,
    },
    morphTo: {
      stroke: '#ef4444', // red
      strokeWidth: 2,
      strokeDasharray: '10,5',
    },
  };

  return styles[type as keyof typeof styles] || {
    stroke: '#6b7280',
    strokeWidth: 2,
  };
};

/**
 * Get relationship color based on type
 */
export const getRelationshipColor = (type: string): string => {
  const colors = {
    hasOne: '#10b981',
    hasMany: '#3b82f6',
    belongsTo: '#f59e0b',
    belongsToMany: '#8b5cf6',
    morphTo: '#ef4444',
  };

  return colors[type as keyof typeof colors] || '#6b7280';
};

/**
 * Process Laravel API response and extract ERD data
 */
export const processApiResponse = (result: any): ErdData => {
  debugApiResponse(result);

  // Handle Laravel response format
  if (result.success && result.data) {
    debugLog('API', 'Using result.data (success format)');
    debugLog('API', `Tables: ${result.data.tables?.length || 0}, Relationships: ${result.data.relationships?.length || 0}`);
    return result.data;
  } else if (result.data) {
    debugLog('API', 'Using result.data (direct format)');
    debugLog('API', `Tables: ${result.data.tables?.length || 0}, Relationships: ${result.data.relationships?.length || 0}`);
    return result.data;
  } else if (result.tables) {
    debugLog('API', 'Using result directly (tables format)');
    debugLog('API', `Tables: ${result.tables?.length || 0}, Relationships: ${result.relationships?.length || 0}`);
    return result;
  } else {
    debugError('API', 'No valid data structure found in response', { availableKeys: Object.keys(result || {}) });
    throw new Error(result.message || 'No data received');
  }
};

/**
 * Create a test edge for debugging when no relationships exist
 */
export const createTestEdge = (nodes: Node[]): Edge | null => {
  if (nodes.length < 2) return null;

  debugLog('DEBUG', 'Creating test edge for debugging');
  const testEdge: Edge = {
    id: 'test-edge',
    source: nodes[0].id,
    target: nodes[1].id,
    type: 'smoothstep',
    style: {
      stroke: '#ff0000',
      strokeWidth: 3,
    },
    label: 'TEST CONNECTION',
    animated: true,
  };
  
  debugLog('DEBUG', 'Test edge created', testEdge);
  return testEdge;
};