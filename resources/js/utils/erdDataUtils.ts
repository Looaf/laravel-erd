import { Node, Edge } from 'reactflow';
import { ErdData } from '../types/erd';

/**
 * Utility functions for converting ERD data to React Flow format
 */

/**
 * Convert ERD tables to React Flow nodes
 */
export const convertTablesToNodes = (erdData: ErdData): Node[] => {

  const nodes = erdData.tables.map((table, index) => {

    // Calculate position - use existing position or create a grid layout
    const position = table.position || calculateGridPosition(index);

    return {
      id: table.id,
      type: 'tableNode',
      position,
      data: {
        table,
        relationships: erdData.relationships.filter(
          rel => {
            const source = rel.source || rel.source_table || rel.from_table;
            const target = rel.target || rel.target_table || rel.to_table;
            return source === table.id || target === table.id;
          }
        ),
      },
      draggable: true,
    };
  });
  
  return nodes;
};

/**
 * Convert ERD relationships to React Flow edges
 * Deduplicates inverse relationships (hasMany/belongsTo pairs)
 */
export const convertRelationshipsToEdges = (relationships: any[], availableNodeIds?: string[]): Edge[] => {

  // Group relationships by table pairs to deduplicate
  const relationshipMap = new Map<string, any>();

  relationships.forEach((rel, index) => {
    // Handle different possible property names from the API
    const sourceId = rel.source || rel.source_table || rel.from_table;
    const targetId = rel.target || rel.target_table || rel.to_table;
    const relationshipType = rel.type || rel.relationship_type;

    if (!sourceId || !targetId) {
      return;
    }

    // Validate that source and target nodes exist (if node IDs provided)
    if (availableNodeIds) {
      if (!availableNodeIds.includes(sourceId)) {
        return;
      }
      if (!availableNodeIds.includes(targetId)) {
        return;
      }
    }

    // Create a consistent key for the relationship pair
    const pairKey = [sourceId, targetId].sort().join('-');


    if (!relationshipMap.has(pairKey)) {
      relationshipMap.set(pairKey, {
        source: sourceId,
        target: targetId,
        relationships: []
      });
    }

    relationshipMap.get(pairKey)!.relationships.push({
      ...rel,
      sourceId,
      targetId,
      relationshipType,
      relationshipId: rel.id || `rel-${index}`
    });
  });


  // Create edges from deduplicated relationships
  const edges: Edge[] = [];
  
  relationshipMap.forEach((pair, pairKey) => {
    const rels = pair.relationships;
    
    
    // Determine the primary relationship direction and type
    let primaryRel = rels[0];
    let label = primaryRel.relationshipType;
    
    // If we have multiple relationships, create a combined label
    if (rels.length > 1) {
      const types = rels.map((r: any) => r.relationshipType);
      
      // Handle common patterns
      if (types.includes('hasMany') && types.includes('belongsTo')) {
        label = '1:N';
      } else if (types.includes('hasOne') && types.includes('belongsTo')) {
        label = '1:1';
      } else if (types.includes('belongsToMany')) {
        label = 'N:N';
      } else {
        label = types.join(' / ');
      }
      
    }

    const edge = {
      id: `edge-${pairKey}`,
      source: pair.source,
      target: pair.target,
      label: label,
      type: 'relationshipEdge',
      data: {
        relationships: rels,
        relationshipCount: rels.length,
        relationshipType: primaryRel.relationshipType,
        sourceKey: primaryRel.sourceKey || primaryRel.local_key || primaryRel.owner_key || 'id',
        foreignKey: primaryRel.foreignKey || primaryRel.foreign_key || 'unknown',
      }
    };

    edges.push(edge);
  });

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
  let processedData: ErdData;

  // Handle Laravel response format
  if (result.success && result.data) {
    processedData = result.data;
  } else if (result.data) {
    processedData = result.data;
  } else if (result.tables) {
    processedData = result;
  } else {
    throw new Error(result.message || 'No data received');
  }

  // Validate that we have the expected structure
  if (!processedData.tables || !Array.isArray(processedData.tables)) {
    throw new Error('Invalid tables data structure');
  }

  if (!processedData.relationships || !Array.isArray(processedData.relationships)) {
    // Don't throw error for relationships, just set empty array
    processedData.relationships = [];
  }

  return processedData;
};

/**
 * Create a test edge for debugging when no relationships exist
 */
export const createTestEdge = (nodes: Node[]): Edge | null => {
  if (nodes.length < 2) return null;

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
  
  return testEdge;
};