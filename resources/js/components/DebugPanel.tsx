import React from 'react';
import { ErdData } from '../types/erd';

interface DebugPanelProps {
  erdData: ErdData | null;
  nodes: any[];
  edges: any[];
}

/**
 * Debug panel component to inspect ERD data structure
 * This is a temporary component for debugging purposes
 */
const DebugPanel: React.FC<DebugPanelProps> = ({ erdData, nodes, edges }) => {
  if (!erdData) return null;

  return (
    <div className="fixed top-0 right-0 w-96 h-screen bg-white border-l border-gray-300 overflow-y-auto z-50 p-4 text-xs">
      <h3 className="font-bold text-lg mb-4">Debug Panel</h3>

      <div className="mb-4">
        <h4 className="font-semibold mb-2">Summary</h4>
        <div className="bg-gray-100 p-2 rounded">
          <div>Tables: {erdData.tables?.length || 0}</div>
          <div>Relationships: {erdData.relationships?.length || 0}</div>
          <div>Generated Nodes: {nodes.length}</div>
          <div>Generated Edges: {edges.length}</div>
        </div>
      </div>

      <div className="mb-4">
        <h4 className="font-semibold mb-2">Tables</h4>
        <div className="bg-gray-100 p-2 rounded max-h-32 overflow-y-auto">
          {erdData.tables?.map((table, i) => (
            <div key={i} className="mb-1">
              <strong>{table.id}</strong>: {table.name}
            </div>
          ))}
        </div>
      </div>

      <div className="mb-4">
        <h4 className="font-semibold mb-2">Raw Relationships</h4>
        <div className="bg-gray-100 p-2 rounded max-h-48 overflow-y-auto">
          {erdData.relationships?.length > 0 ? (
            erdData.relationships.map((rel: any, i) => (
              <div key={i} className="mb-2 p-2 bg-white rounded border">
                <div><strong>Index:</strong> {i}</div>
                <div><strong>ID:</strong> {rel.id || 'missing'}</div>
                <div><strong>Source:</strong> {rel.source || rel.source_table || rel.from_table || 'missing'}</div>
                <div><strong>Target:</strong> {rel.target || rel.target_table || rel.to_table || 'missing'}</div>
                <div><strong>Type:</strong> {rel.type || rel.relationship_type || 'missing'}</div>
                <div><strong>All Keys:</strong> {Object.keys(rel).join(', ')}</div>
                <details className="mt-1">
                  <summary className="cursor-pointer text-blue-600">Raw Data</summary>
                  <pre className="mt-1 text-xs bg-gray-50 p-1 rounded overflow-x-auto">
                    {JSON.stringify(rel, null, 2)}
                  </pre>
                </details>
              </div>
            ))
          ) : (
            <div>No relationships found</div>
          )}
        </div>
      </div>

      <div className="mb-4">
        <h4 className="font-semibold mb-2">Generated Edges</h4>
        <div className="bg-gray-100 p-2 rounded max-h-32 overflow-y-auto">
          {edges.length > 0 ? (
            edges.map((edge, i) => (
              <div key={i} className="mb-1">
                <strong>{edge.id}</strong>: {edge.source} → {edge.target} ({edge.type})
              </div>
            ))
          ) : (
            <div className="text-red-600">No edges generated!</div>
          )}
        </div>
      </div>

      <div className="mb-4">
        <button
          onClick={() => {
            console.log('=== ERD DEBUG DATA ===');
            console.log('Raw ERD Data:', erdData);
            console.log('Generated Nodes:', nodes);
            console.log('Generated Edges:', edges);
            console.log('=== END DEBUG DATA ===');
          }}
          className="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700"
        >
          Log to Console
        </button>
      </div>
    </div>
  );
};

export default DebugPanel;