import React, { useState } from 'react';
import { useStore, Handle, Position } from 'reactflow';
import { Table, Relationship, Column } from '../../types/erd';
import { GripVertical } from 'lucide-react';
import { TableColumn } from './TableColumn';

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
        {table.columns.map((column) => (
          <TableColumn key={column.name} table={table} column={column} />
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