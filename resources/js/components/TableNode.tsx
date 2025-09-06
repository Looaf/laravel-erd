import React, { useState } from 'react';
import { useStore, Handle, Position } from 'reactflow';
import { Table, Relationship, Column } from '../types/erd';
import { GripVertical } from 'lucide-react';

interface TableNodeProps {
  table: Table;
  relationships: Relationship[];
}

const TableNode: React.FC<TableNodeProps> = ({ table, relationships }) => {
  const [isHovered, setIsHovered] = useState<boolean>(false);
  const [isDragging, setIsDragging] = useState<boolean>(false);
  
  // Get dragging state from React Flow store
  const nodeInternals = useStore((state) => state.nodeInternals);
  const currentNode = nodeInternals.get(table.id);
  const isBeingDragged = currentNode?.dragging || false;

  const getColumnIcon = (column: Column): string => {
    if (column.primary) return '🔑';
    if (column.unique) return '🔒';
    if (!column.nullable) return '❗';
    return '';
  };

  const getColumnTypeColor = (type: string): string => {
    const typeColors: Record<string, string> = {
      'bigint': 'text-blue-600',
      'int': 'text-blue-600',
      'integer': 'text-blue-600',
      'varchar': 'text-green-600',
      'string': 'text-green-600',
      'text': 'text-green-600',
      'boolean': 'text-purple-600',
      'datetime': 'text-orange-600',
      'timestamp': 'text-orange-600',
      'date': 'text-orange-600',
      'json': 'text-red-600',
      'decimal': 'text-yellow-600',
      'float': 'text-yellow-600',
    };
    
    return typeColors[type.toLowerCase()] || 'text-gray-600';
  };

  const getRelationshipSummary = (): string => {
    if (relationships.length === 0) return '';
    
    const relationshipTypes = relationships.reduce((acc, rel) => {
      const type = rel.source === table.id ? rel.type : 'inverse';
      acc[type] = (acc[type] || 0) + 1;
      return acc;
    }, {} as Record<string, number>);

    const summary = Object.entries(relationshipTypes)
      .map(([type, count]) => `${count} ${type}`)
      .join(', ');
    
    return `${relationships.length} relationship${relationships.length !== 1 ? 's' : ''}: ${summary}`;
  };

  return (
    <div
      style={{
        background: 'white',
        border: isBeingDragged ? '2px solid #3b82f6' : isHovered ? '1px solid #60a5fa' : '1px solid #ddd',
        borderRadius: '4px',
        minWidth: '200px',
        maxWidth: '320px',
        fontSize: '12px',
        boxShadow: isBeingDragged ? '0 10px 25px rgba(0,0,0,0.15)' : isHovered ? '0 4px 12px rgba(0,0,0,0.1)' : '0 1px 3px rgba(0,0,0,0.1)',
        transform: isBeingDragged ? 'scale(1.02)' : 'scale(1)',
        transition: 'all 0.2s ease'
      }}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Handles for connections */}
      <Handle type="target" position={Position.Top} />
      <Handle type="source" position={Position.Bottom} />
      <Handle type="target" position={Position.Left} />
      <Handle type="source" position={Position.Right} />

      {/* Table Header */}
      <div style={{
        background: '#3b82f6',
        color: 'white',
        padding: '8px',
        fontWeight: 'bold',
        borderRadius: '4px 4px 0 0',
        cursor: 'grab',
        display: 'flex',
        alignItems: 'center',
        justifyContent: 'space-between'
      }}>
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', flex: 1, minWidth: 0 }}>
          {/* Drag handle indicator */}
          <GripVertical size={16} style={{ opacity: 0.7, flexShrink: 0 }} />
          <h3 style={{ fontWeight: 'bold', fontSize: '14px', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
            {table.name}
          </h3>
        </div>
        <div style={{ fontSize: '10px', opacity: 0.9, flexShrink: 0, marginLeft: '8px' }}>
          {table.columns.length}
        </div>
      </div>

      {/* Columns */}
      <div style={{ padding: '4px', maxHeight: '200px', overflowY: 'auto' }}>
        {table.columns.slice(0, 5).map((column) => (
          <div
            key={`${table.id}-${column.name}`}
            style={{
              padding: '2px 4px',
              borderBottom: '1px solid #eee',
              display: 'flex',
              justifyContent: 'space-between',
              alignItems: 'center',
              backgroundColor: column.primary ? '#eff6ff' : 'transparent',
              borderLeft: column.primary ? '3px solid #3b82f6' : 'none'
            }}
          >
            <span style={{ 
              fontWeight: column.primary ? 'bold' : 'normal',
              fontSize: '11px',
              display: 'flex',
              alignItems: 'center',
              gap: '4px',
              flex: 1,
              minWidth: 0,
              overflow: 'hidden',
              textOverflow: 'ellipsis',
              whiteSpace: 'nowrap'
            }}>
              <span style={{ width: '16px', textAlign: 'center', flexShrink: 0 }} title={
                column.primary ? 'Primary Key' : 
                column.unique ? 'Unique' : 
                !column.nullable ? 'Not Nullable' : ''
              }>
                {getColumnIcon(column)}
              </span>
              {column.name}
            </span>
            <span style={{ 
              color: '#666', 
              fontSize: '10px',
              fontFamily: 'monospace',
              flexShrink: 0,
              marginLeft: '8px'
            }}>
              {column.type}
              {column.nullable && <span style={{ color: '#999' }} title="Nullable">?</span>}
            </span>
          </div>
        ))}
        {table.columns.length > 5 && (
          <div style={{ padding: '2px 4px', color: '#666', fontSize: '10px' }}>
            +{table.columns.length - 5} more...
          </div>
        )}
      </div>

      {/* Relationships count - show unique table connections */}
      {relationships.length > 0 && (
        <div style={{
          padding: '4px 8px',
          background: '#f5f5f5',
          fontSize: '10px',
          color: '#666',
          borderRadius: '0 0 4px 4px'
        }}>
          {/* Count unique connected tables */}
          {(() => {
            const connectedTables = new Set();
            relationships.forEach(rel => {
              if (rel.source === table.id) connectedTables.add(rel.target);
              if (rel.target === table.id) connectedTables.add(rel.source);
            });
            const count = connectedTables.size;
            return `${count} connection${count !== 1 ? 's' : ''}`;
          })()}
        </div>
      )}
    </div>
  );
};

export default TableNode;