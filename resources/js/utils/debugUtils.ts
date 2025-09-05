/**
 * Debug utility functions for ERD development
 */

export const debugLog = (category: string, message: string, data?: any) => {
  const timestamp = new Date().toISOString();
  const prefix = `[${timestamp}] [ERD-${category}]`;
  
  if (data) {
    console.log(`${prefix} ${message}`, data);
  } else {
    console.log(`${prefix} ${message}`);
  }
};

export const debugError = (category: string, message: string, error?: any) => {
  const timestamp = new Date().toISOString();
  const prefix = `[${timestamp}] [ERD-${category}] ERROR`;
  
  if (error) {
    console.error(`${prefix} ${message}`, error);
  } else {
    console.error(`${prefix} ${message}`);
  }
};

export const debugTable = (category: string, message: string, data: any[]) => {
  const timestamp = new Date().toISOString();
  const prefix = `[${timestamp}] [ERD-${category}]`;
  
  console.log(`${prefix} ${message}`);
  console.table(data);
};

export const debugApiResponse = (response: any) => {
  debugLog('API', 'Raw API Response received');
  debugLog('API', `Response type: ${typeof response}`);
  debugLog('API', `Response keys: ${Object.keys(response || {}).join(', ')}`);
  
  if (response?.data) {
    debugLog('API', 'Response has data property');
    debugLog('API', `Data type: ${typeof response.data}`);
    debugLog('API', `Data keys: ${Object.keys(response.data || {}).join(', ')}`);
  }
  
  if (response?.tables) {
    debugLog('API', `Tables found: ${response.tables.length}`);
  }
  
  if (response?.relationships) {
    debugLog('API', `Relationships found: ${response.relationships.length}`);
  }
  
  console.log('Full API Response:', response);
};

export const debugFlowData = (nodes: any[], edges: any[]) => {
  debugLog('FLOW', `Generated ${nodes.length} nodes and ${edges.length} edges`);
  
  if (nodes.length > 0) {
    debugTable('FLOW', 'Generated Nodes:', nodes.map(n => ({
      id: n.id,
      type: n.type,
      position: `${n.position.x},${n.position.y}`,
      tableName: n.data?.table?.name || 'unknown'
    })));
  }
  
  if (edges.length > 0) {
    debugTable('FLOW', 'Generated Edges:', edges.map(e => ({
      id: e.id,
      source: e.source,
      target: e.target,
      type: e.type,
      relationshipType: e.data?.relationshipType || 'unknown'
    })));
  }
};